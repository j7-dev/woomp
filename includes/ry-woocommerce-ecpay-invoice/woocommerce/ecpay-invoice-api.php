<?php
class RY_WEI_Invoice_Api extends RY_ECPay_Invoice
{
    public static $api_test_url = [
        'get' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/Issue',
        'getDelay' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/DelayIssue',
        'invalid' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/Invalid',
        'checkMobile' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/CheckBarcode',
        'checkDonate' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/CheckLoveCode',
    ];

    public static $api_url = [
        'get' => 'https://einvoice.ecpay.com.tw/B2CInvoice/Issue',
        'getDelay' => 'https://einvoice.ecpay.com.tw/B2CInvoice/DelayIssue',
        'invalid' => 'https://einvoice.ecpay.com.tw/B2CInvoice/Invalid',
        'checkMobile' => 'https://einvoice.ecpay.com.tw/B2CInvoice/CheckBarcode',
        'checkDonate' => 'https://einvoice.ecpay.com.tw/B2CInvoice/CheckLoveCode',
    ];

    public static function get($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        if ($order->get_meta('_invoice_number')) {
            return false;
        }

        list($MerchantID, $HashKey, $HashIV) = RY_WEI_Invoice::get_ecpay_api_info();

        $data = self::make_get_data($order, $MerchantID);
        if ($data['SalesAmount'] == 0) {
            $order->update_meta_data('_invoice_number', 'zero');
            $order->save_meta_data();
            $order->add_order_note(__('Zero total fee without invoice', 'ry-woocommerce-ecpay-invoice'));
            return;
        }

        $args = self::build_args($data, $MerchantID);
        do_action('ry_wei_get_invoice', $args, $order);

        RY_WEI_Invoice::log('Create POST: ' . var_export($args, true));

        if ('yes' === RY_WEI::get_option('ecpay_testmode', 'yes')) {
            $post_url = self::$api_test_url['get'];
        } else {
            $post_url = self::$api_url['get'];
        }
        $result = self::link_server($post_url, $args, $HashKey, $HashIV);

        if ($result == '') {
            return;
        }

        if ($result->RtnCode != 1) {
            $order->add_order_note(sprintf(
                /* translators: %s Error messade */
                __('Get invoice error: %s', 'ry-woocommerce-ecpay-invoice'),
                $result->RtnMsg
            ));
            return;
        }

        if (apply_filters('ry_wei_add_api_success_notice', true)) {
            $order->add_order_note(
                __('Invoice number', 'ry-woocommerce-ecpay-invoice') . ': ' . $result->InvoiceNo . "\n"
                . __('Invoice random number', 'ry-woocommerce-ecpay-invoice') . ': ' . $result->RandomNumber . "\n"
                . __('Invoice create time', 'ry-woocommerce-ecpay-invoice') . ': ' . $result->InvoiceDate . "\n"
            );
        }

        $order->update_meta_data('_invoice_number', $result->InvoiceNo);
        $order->update_meta_data('_invoice_random_number', $result->RandomNumber);
        $order->update_meta_data('_invoice_date', $result->InvoiceDate);
        $order->update_meta_data('_invoice_ecpay_RelateNumber', $data['RelateNumber']);
        $order->save_meta_data();

        do_action('ry_wei_get_invoice_response', $result, $order);
    }

    public static function get_delay($order_id)
    {
        $delay_days = (int) RY_WEI::get_option('get_delay_days', 0);
        if ($delay_days <= 0) {
            return self::get($order_id);
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        if ($order->get_meta('_invoice_number')) {
            return false;
        }

        list($MerchantID, $HashKey, $HashIV) = RY_WEI_Invoice::get_ecpay_api_info();

        $data = self::make_get_data($order, $MerchantID);
        if ($data['SalesAmount'] == 0) {
            $order->update_meta_data('_invoice_number', 'zero');
            $order->save_meta_data();
            $order->add_order_note(__('Zero total fee without invoice', 'ry-woocommerce-ecpay-invoice'));
            return;
        }

        $data['DelayFlag'] = '1';
        $data['DelayDay'] = $delay_days;
        $data['Tsr'] = $data['RelateNumber'];
        $data['PayType'] = '2';
        $data['PayAct'] = 'ECPAY';
        $data['NotifyURL'] = WC()->api_request_url('ry_wei_delay_callback', true);
        unset($data['vat']);

        $args = self::build_args($data, $MerchantID);
        do_action('ry_wei_get_invoice', $args, $order);

        RY_WEI_Invoice::log('Create POST: ' . var_export($args, true));

        if ('yes' === RY_WEI::get_option('ecpay_testmode', 'yes')) {
            $post_url = self::$api_test_url['getDelay'];
        } else {
            $post_url = self::$api_url['getDelay'];
        }
        $result = self::link_server($post_url, $args, $HashKey, $HashIV);

        if ($result == '') {
            return;
        }

        if ($result->RtnCode != 1) {
            $order->add_order_note(sprintf(
                /* translators: %s Error messade */
                __('Get invoice error: %s', 'ry-woocommerce-ecpay-invoice'),
                $result->RtnMsg
            ));
            return;
        }

        if (apply_filters('ry_wei_add_api_success_notice', true)) {
            $order->add_order_note(
                __('Delay get invoice', 'ry-woocommerce-ecpay-invoice') . ': ' . $result->OrderNumber . "\n"
            );
        }

        $order->update_meta_data('_invoice_number', 'delay');
        $order->update_meta_data('_invoice_ecpay_RelateNumber', $data['RelateNumber']);
        $order->save_meta_data();

        do_action('ry_wei_get_dalay_invoice_response', $result, $order);
    }

    protected static function make_get_data($order, $MerchantID)
    {
        $country = $order->get_billing_country();
        $countries = WC()->countries->get_countries();
        $full_country = ($country && isset($countries[$country])) ? $countries[$country] : $country;

        $state = $order->get_billing_state();
        $states = WC()->countries->get_states($country);
        $full_state = ($state && isset($states[$state])) ? $states[$state] : $state;

        $data = [
            'MerchantID' => $MerchantID,
            'RelateNumber' => self::generate_trade_no($order->get_id(), RY_WEI::get_option('order_prefix')),
            'CustomerID' => '',
            'CustomerIdentifier' => '',
            'CustomerName' => $order->get_billing_last_name() . $order->get_billing_first_name(),
            'CustomerAddr' => $full_country . $full_state . $order->get_billing_city() . $order->get_billing_address_1() . $order->get_billing_address_2(),
            'CustomerPhone' => '',
            'CustomerEmail' => $order->get_billing_email(),
            'Print' => '0',
            'Donation' => '0',
            'LoveCode' => '',
            'CarrierType' => '',
            'CarrierNum' => '',
            'TaxType' => '1',
            'SalesAmount' => intval(round($order->get_total(), 0)),
            'InvoiceRemark' => $order->get_id(),
            'Items' => [],
            'InvType' => '07',
            'vat' => '1',
        ];

        switch ($order->get_meta('_invoice_type')) {
            case 'personal':
                switch ($order->get_meta('_invoice_carruer_type')) {
                    case 'none':
                        $data['Print'] = '1';
                        break;
                    case 'ecpay_host':
                        $data['CarrierType'] = '1';
                        break;
                    case 'MOICA':
                        $data['CarrierType'] = '2';
                        $data['CarrierNum'] = $order->get_meta('_invoice_carruer_no');
                        break;
                    case 'phone_barcode':
                        $data['CarrierType'] = '3';
                        $data['CarrierNum'] = $order->get_meta('_invoice_carruer_no');
                        break;
                }
                break;
            case 'company':
                $data['Print'] = '1';
                $data['CustomerIdentifier'] = $order->get_meta('_invoice_no');
                $company = $order->get_billing_company();
                if ($company) {
                    $data['CustomerName'] = $company;
                }
                break;
            case 'donate':
                $data['Donation'] = '1';
                $data['LoveCode'] = $order->get_meta('_invoice_donate_no');
                break;
        }

        $items = $order->get_items(['line_item', 'fee']);
        if (count($items)) {
            foreach ($items as $item) {
                $data['Items'][] = [
                    'ItemSeq' => count($data['Items']) + 1,
                    'ItemName' => mb_substr($item->get_name(), 0, 100),
                    'ItemCount' => $item->get_quantity(),
                    'ItemWord' => __('parcel', 'ry-woocommerce-ecpay-invoice'),
                    'ItemPrice' => round($item->get_total() / $item->get_quantity(), 4),
                    'ItemTaxType' => '1',
                    'ItemAmount' => round($item->get_total(), 2)
                ];
            }
        }

        $shipping_fee = $order->get_shipping_total();
        if ($shipping_fee != 0) {
            $data['Items'][] = [
                'ItemSeq' => count($data['Items']) + 1,
                'ItemName' => __('shipping fee', 'ry-woocommerce-ecpay-invoice'),
                'ItemCount' => 1,
                'ItemWord' => __('parcel', 'ry-woocommerce-ecpay-invoice'),
                'ItemPrice' => $shipping_fee,
                'ItemTaxType' => '1',
                'ItemAmount' => $shipping_fee
            ];
        }

        $data['InvoiceRemark'] = apply_filters('ry_wei_invoice_remark', $data['InvoiceRemark'], $data, $order);
        $data['InvoiceRemark'] = mb_substr($data['InvoiceRemark'], 0, 200);

        return $data;
    }

    public static function invalid($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        $invoice_number = $order->get_meta('_invoice_number');

        if ($invoice_number == 'zero') {
            $order->delete_meta_data('_invoice_number');
            $order->save_meta_data();
            return;
        }

        if (!$invoice_number) {
            return false;
        }

        list($MerchantID, $HashKey, $HashIV) = RY_WEI_Invoice::get_ecpay_api_info();
        $data = [
            'MerchantID' => $MerchantID,
            'InvoiceNo' => $invoice_number,
            'InvoiceDate' => $order->get_meta('_invoice_date'),
            'Reason' => __('Invalid invoice', 'ry-woocommerce-ecpay-invoice'),
        ];

        $args = self::build_args($data, $MerchantID);
        do_action('ry_wei_invalid_invoice', $args, $order);

        RY_WEI_Invoice::log('Invalid POST: ' . var_export($args, true));

        if ('yes' === RY_WEI::get_option('ecpay_testmode', 'yes')) {
            $post_url = self::$api_test_url['invalid'];
        } else {
            $post_url = self::$api_url['invalid'];
        }
        $result = self::link_server($post_url, $args, $HashKey, $HashIV);

        if ($result == '') {
            return;
        }

        if ($result->RtnCode != 1) {
            $order->add_order_note(sprintf(
                /* translators: %s Error messade */
                __('Invalid invoice error: %s', 'ry-woocommerce-ecpay-invoice'),
                $result->RtnMsg
            ));
            return;
        }

        if (apply_filters('ry_wei_add_api_success_notice', true)) {
            $order->add_order_note(
                __('Invalid invoice', 'ry-woocommerce-ecpay-invoice') . ': ' . $result->InvoiceNo
            );
        }

        $order->delete_meta_data('_invoice_number');
        $order->delete_meta_data('_invoice_random_number');
        $order->delete_meta_data('_invoice_ecpay_RelateNumber');
        $order->save_meta_data();

        do_action('ry_wei_invalid_invoice_response', $result, $order);
    }

    public static function check_mobile_code($code)
    {
        list($MerchantID, $HashKey, $HashIV) = RY_WEI_Invoice::get_ecpay_api_info();

        $data = [
            'MerchantID' => $MerchantID,
            'BarCode' => $code
        ];
        $args = self::build_args($data, $MerchantID);

        RY_WEI_Invoice::log('Check mobile POST: ' . var_export($args, true));

        if ('yes' === RY_WEI::get_option('ecpay_testmode', 'yes')) {
            $post_url = self::$api_test_url['checkMobile'];
        } else {
            $post_url = self::$api_url['checkMobile'];
        }

        $result = self::link_server($post_url, $args, $HashKey, $HashIV);

        if ($result == '') {
            return false;
        }

        return $result->RtnCode == 1 && $result->IsExist == 'Y';
    }

    public static function check_donate_no($code)
    {
        list($MerchantID, $HashKey, $HashIV) = RY_WEI_Invoice::get_ecpay_api_info();

        $data = [
            'MerchantID' => $MerchantID,
            'LoveCode' => $code
        ];
        $args = self::build_args($data, $MerchantID);

        RY_WEI_Invoice::log('Check donate POST: ' . var_export($args, true));

        if ('yes' === RY_WEI::get_option('ecpay_testmode', 'yes')) {
            $post_url = self::$api_test_url['checkDonate'];
        } else {
            $post_url = self::$api_url['checkDonate'];
        }

        $result = self::link_server($post_url, $args, $HashKey, $HashIV);

        if ($result == '') {
            return false;
        }

        return $result->RtnCode == 1 && $result->IsExist == 'Y';
    }
}
