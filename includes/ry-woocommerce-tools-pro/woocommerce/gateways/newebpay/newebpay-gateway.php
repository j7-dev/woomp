<?php
final class RY_WTP_NewebPay_Gateway
{
    public static function init()
    {
        include_once RY_WTP_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/newebpay-gateway-credit-installment-base.php';
        include_once RY_WTP_PLUGIN_DIR . 'woocommerce/gateways/newebpay/newebpay-gateway-credit-installment-3.php';
        include_once RY_WTP_PLUGIN_DIR . 'woocommerce/gateways/newebpay/newebpay-gateway-credit-installment-6.php';
        include_once RY_WTP_PLUGIN_DIR . 'woocommerce/gateways/newebpay/newebpay-gateway-credit-installment-12.php';
        include_once RY_WTP_PLUGIN_DIR . 'woocommerce/gateways/newebpay/newebpay-gateway-credit-installment-18.php';
        include_once RY_WTP_PLUGIN_DIR . 'woocommerce/gateways/newebpay/newebpay-gateway-credit-installment-24.php';
        include_once RY_WTP_PLUGIN_DIR . 'woocommerce/gateways/newebpay/newebpay-gateway-credit-installment-30.php';

        if (is_admin()) {
            //add_filter('woocommerce_get_settings_rytools', [__CLASS__, 'add_setting'], 11, 2);
        }

        if ('yes' === RY_WT::get_option('newebpay_gateway', 'no')) {
            if ('yes' === RY_WTP::get_option('newebpay_credit_installment', 'no')) {
                add_filter('woocommerce_payment_gateways', [__CLASS__, 'add_method']);
            }

            if ('yes' === RY_WTP::get_option('newebpay_email_payment_info', 'yes')) {
                add_action('woocommerce_email_after_order_table', [__CLASS__, 'add_payment_info'], 10, 4);
            }
        }
    }

    public static function add_setting($settings, $current_section)
    {
        if ($current_section == 'newebpay_gateway') {
            $setting_id_idx = array_column($settings, 'id');

            $setting_idx = array_search('api_options', $setting_id_idx);
            array_splice($settings, $setting_idx, 0, [
                [
                    'title' => __('Gateway options', 'ry-woocommerce-tools-pro'),
                    'id' => 'gateway_options',
                    'type' => 'title'
                ],
                [
                    'title' => __('Credit installment', 'ry-woocommerce-tools-pro'),
                    'id' => RY_WTP::$option_prefix . 'newebpay_credit_installment',
                    'type' => 'checkbox',
                    'default' => 'no',
                    'desc' => __('Add each periods of credit installment as a payment gateway.', 'ry-woocommerce-tools-pro')
                ],
                [
                    'title' => __('Show payment info in email', 'ry-woocommerce-tools-pro'),
                    'id' => RY_WTP::$option_prefix . 'newebpay_email_payment_info',
                    'type' => 'checkbox',
                    'default' => 'yes',
                    'desc' => sprintf(
                        /* translators: %s: email title */
                        __('Add payment info in "%s" email.', 'ry-woocommerce-tools-pro'),
                        __('Order on-hold', 'woocommerce')
                    )
                ],
                [
                    'id' => 'gateway_options',
                    'type' => 'sectionend'
                ]
            ]);
        }
        return $settings;
    }

    public static function add_method($methods)
    {
        $methods[] = 'RY_NewebPay_Gateway_Credit_Installment_3';
        $methods[] = 'RY_NewebPay_Gateway_Credit_Installment_6';
        $methods[] = 'RY_NewebPay_Gateway_Credit_Installment_12';
        $methods[] = 'RY_NewebPay_Gateway_Credit_Installment_18';
        $methods[] = 'RY_NewebPay_Gateway_Credit_Installment_24';
        $methods[] = 'RY_NewebPay_Gateway_Credit_Installment_30';

        return $methods;
    }

    public static function add_payment_info($order, $sent_to_admin, $plain_text, $email)
    {
        if ($email->id == 'customer_on_hold_order') {
            switch ($order->get_payment_method()) {
                case 'ry_newebpay_atm':
                    $template_file = 'emails/email-order-newebpay-payment-info-atm.php';
                    break;
                case 'ry_newebpay_barcode':
                    $template_file = 'emails/email-order-newebpay-payment-info-barcode.php';
                    break;
                case 'ry_newebpay_cvs':
                    $template_file = 'emails/email-order-newebpay-payment-info-cvs.php';
                    break;
            }

            if (isset($template_file)) {
                if ($plain_text) {
                    wc_get_template(
                        str_replace('emails/', 'emails/plain/', $template_file),
                        array(
                            'order'         => $order,
                            'sent_to_admin' => $sent_to_admin,
                            'plain_text'    => $plain_text,
                            'email'         => $email,
                        ),
                        '',
                        RY_WTP_PLUGIN_DIR . 'templates/'
                    );
                } else {
                    wc_get_template(
                        $template_file,
                        array(
                            'order'         => $order,
                            'sent_to_admin' => $sent_to_admin,
                            'plain_text'    => $plain_text,
                            'email'         => $email,
                        ),
                        '',
                        RY_WTP_PLUGIN_DIR . 'templates/'
                    );
                }
            }
        }
    }
}

RY_WTP_NewebPay_Gateway::init();
