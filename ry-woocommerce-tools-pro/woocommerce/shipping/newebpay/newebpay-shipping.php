<?php
final class RY_WTP_NewebPay_Shipping
{
    public static function init()
    {
        include_once RY_WTP_PLUGIN_DIR . 'woocommerce/shipping/ry-base.php';
        include_once RY_WTP_PLUGIN_DIR . 'woocommerce/shipping/newebpay/newebpay-shipping-cvs.php';

        if (is_admin()) {
            add_filter('woocommerce_get_settings_rytools', [__CLASS__, 'add_setting'], 11, 2);
        }

        if ('yes' === RY_WT::get_option('newebpay_shipping', 'no')) {
            add_filter('woocommerce_shipping_methods', [__CLASS__, 'use_pro_method'], 11);
            add_filter('woocommerce_checkout_fields', [__CLASS__, 'hide_billing_info'], 9999);

            if (is_admin()) {
            } else {
                add_action('woocommerce_review_order_after_shipping', [__CLASS__, 'shipping_choose_cvs']);
                add_filter('woocommerce_update_order_review_fragments', [__CLASS__, 'shipping_choose_cvs_info'], 11);
            }
        }
    }

    public static function add_setting($settings, $current_section)
    {
        if ($current_section == 'newebpay_shipping') {
            $setting_id_idx = array_column($settings, 'id');

            $setting_idx = array_search(RY_WT::$option_prefix . 'newebpay_shipping', $setting_id_idx);
            array_splice($settings, $setting_idx + 1, 0, [
                [
                    'title' => __('cvs remove billing address', 'ry-woocommerce-tools-pro'),
                    'id' => RY_WTP::$option_prefix . 'newebpay_cvs_billing_address',
                    'type' => 'checkbox',
                    'default' => 'no',
                    'desc' => __('Remove billing address when shipping mode is cvs.', 'ry-woocommerce-tools-pro') . '<br>'
                        . __('The billing address still will show in order details.', 'ry-woocommerce-tools-pro')
                ]
            ]);
        }
        return $settings;
    }

    public static function use_pro_method($shipping_methods)
    {
        if (isset($shipping_methods['ry_newebpay_shipping_cvs'])) {
            $shipping_methods['ry_newebpay_shipping_cvs'] = 'RY_NewebPay_Shipping_CVS_Pro';
        }

        return $shipping_methods;
    }

    public static function hide_billing_info($fields)
    {
        if (is_checkout()) {
            $chosen_method = isset(WC()->session->chosen_shipping_methods) ? WC()->session->chosen_shipping_methods : [];
            $is_support = false;
            if (count($chosen_method)) {
                foreach (RY_NewebPay_Shipping::$support_methods as $method => $method_class) {
                    if (strpos($chosen_method[0], $method) === 0) {
                        $is_support = true;
                    }
                }
            }

            if ($is_support) {
                if ('yes' == RY_WTP::get_option('ecpay_cvs_billing_address', 'no')) {
                    if (strpos($chosen_method[0], '_cvs')) {
                        $hide_fields = ['billing_country', 'billing_address_1', 'billing_address_2', 'billing_city', 'billing_state', 'billing_postcode'];
                        foreach ($hide_fields as $field_name) {
                            if (isset($fields['billing'][$field_name])) {
                                $fields['billing'][$field_name]['class'][] = 'ry-hide';
                            }
                        }
                    }
                }
            }
        }

        if (did_action('woocommerce_checkout_process')) {
            if (RY_WTP::get_option('newebpay_cvs_billing_address', 'no') == 'yes') {
                $used_cvs = false;
                $shipping_method = isset($_POST['shipping_method']) ? wc_clean($_POST['shipping_method']) : [];
                foreach ($shipping_method as $method) {
                    $method = strstr($method, ':', true);
                    if (array_key_exists($method, RY_NewebPay_Shipping::$support_methods)) {
                        if (strpos($method, '_cvs')) {
                            $used_cvs = true;
                        }
                        break;
                    }
                }

                if ($used_cvs) {
                    $fields['billing']['billing_country']['required'] = false;
                    $fields['billing']['billing_address_1']['required'] = false;
                    $fields['billing']['billing_address_2']['required'] = false;
                    $fields['billing']['billing_city']['required'] = false;
                    $fields['billing']['billing_state']['required'] = false;
                    $fields['billing']['billing_postcode']['required'] = false;
                }
            }
        }

        return $fields;
    }

    public static function shipping_choose_cvs()
    {
        wp_enqueue_script('ry-pro-shipping');
    }

    public static function shipping_choose_cvs_info($fragments)
    {
        if (isset($fragments['newebpay_shipping_info'])) {
            if (RY_WTP::get_option('newebpay_cvs_billing_address', 'no') == 'yes') {
                $chosen_method = isset(WC()->session->chosen_shipping_methods) ? WC()->session->chosen_shipping_methods : [];
                $fragments['hide_billing_address'] = strpos($chosen_method[0], '_cvs');
            }
        }

        return $fragments;
    }
}

RY_WTP_NewebPay_Shipping::init();
