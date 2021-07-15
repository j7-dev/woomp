<?php
class RY_WEI_Invoice_Response extends RY_WEI_Invoice_Api
{
    public static function init()
    {
        add_action('woocommerce_api_ry_wei_delay_callback', [__CLASS__, 'check_callback']);

        add_action('valid_wei_callback_request', [__CLASS__, 'doing_callback']);
    }

    public static function check_callback()
    {
        RY_WEI_Invoice::log('IPN post request: ' . var_export($_POST, true));
        RY_WEI_Invoice::log('IPN get request: ' . var_export($_GET, true));
        if (!empty($_POST)) {
            $ipn_info = wp_unslash($_POST);
            if (self::ipn_request_is_valid($ipn_info)) {
                do_action('valid_wei_callback_request', $ipn_info);
            } else {
                self::die_error();
            }
        } else {
            self::die_error();
        }
    }

    protected static function ipn_request_is_valid($ipn_info)
    {
        if (isset($ipn_info['inv_mer_id'])) {
            RY_WEI_Invoice::log('IPN request: ' . var_export($ipn_info, true));
            list($MerchantID, $HashKey, $HashIV) = RY_WEI_Invoice::get_ecpay_api_info();

            if ($ipn_info['inv_mer_id'] == $MerchantID) {
                return true;
            } else {
                RY_WEI_Invoice::log('IPN request check failed.', 'error');
            }
        }
        return false;
    }

    public static function doing_callback($ipn_info)
    {
        $order_id = self::get_order_id($ipn_info, RY_WEI::get_option('order_prefix'));
        if ($order = wc_get_order($order_id)) {
            if (isset($ipn_info['invoicenumber'], $ipn_info['invoicecode'], $ipn_info['invoicedate'], $ipn_info['invoicetime'])) {
                if (!empty($ipn_info['invoicenumber']) && $order->get_meta('_invoice_ecpay_RelateNumber') == $ipn_info['od_sob']) {
                    $order->update_meta_data('_invoice_number', $ipn_info['invoicenumber']);
                    $order->update_meta_data('_invoice_random_number', $ipn_info['invoicecode']);
                    $order->update_meta_data('_invoice_date', $ipn_info['invoicedate'] . ' ' . $ipn_info['invoicetime']);
                    $order->save_meta_data();
                    self::die_success();
                } else {
                    RY_WEI_Invoice::log('Error invoice info', 'error');
                }
            }
            RY_WEI_Invoice::log('Lost invoice info', 'error');
        } else {
            RY_WEI_Invoice::log('Order not found', 'error');
        }
        self::die_error();
    }
}
