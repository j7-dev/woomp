<?php
final class RY_WEI_Invoice_setting
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;
        }

        if (is_admin()) {
            add_filter('woocommerce_get_sections_rytools', [__CLASS__, 'add_sections'], 11);
            add_filter('woocommerce_get_settings_rytools', [__CLASS__, 'add_setting'], 10, 2);
            add_action('woocommerce_update_options_rytools_ecpay_invoice', [__CLASS__, 'check_option']);
            add_filter('ry_setting_section_tools', '__return_false');
            add_action('ry_setting_section_ouput_tools', [__CLASS__, 'output_tools'], 11);
        }
    }

    public static function add_sections($sections)
    {
        if (isset($sections['tools'])) {
            $add_idx = array_search('tools', array_keys($sections));
            $sections = array_slice($sections, 0, $add_idx) + [
                'ecpay_invoice' => __('ECPay invoice', 'ry-woocommerce-ecpay-invoice')
            ] + array_slice($sections, $add_idx);
        } else {
            $sections['ecpay_invoice'] = __('ECPay invoice', 'ry-woocommerce-ecpay-invoice');
            $sections['tools'] = __('Tools', 'ry-woocommerce-ecpay-invoice');
        }

        return $sections;
    }

    public static function add_setting($settings, $current_section)
    {
        if ($current_section == 'ecpay_invoice') {
            $settings = include RY_WEI_PLUGIN_DIR . 'woocommerce/settings/settings-ecpay-invoice.php';
        }
        return $settings;
    }

    public static function check_option()
    {
        if ('yes' == RY_WEI::get_option('enabled_invoice', 'no')) {
            $enable_list = apply_filters('enable_ry_invoice', []);
            if (count($enable_list) == 1) {
                if ($enable_list != ['ecpay']) {
                    WC_Admin_Settings::add_error(__('Not recommended enable two invoice module/plugin at the same time!', 'ry-woocommerce-ecpay-invoice'));
                }
            } elseif (count($enable_list) > 1) {
                WC_Admin_Settings::add_error(__('Not recommended enable two invoice module/plugin at the same time!', 'ry-woocommerce-ecpay-invoice'));
            }

            if ('yes' != RY_WEI::get_option('ecpay_testmode', 'yes')) {
                if (empty(RY_WEI::get_option('ecpay_MerchantID')) || empty(RY_WEI::get_option('ecpay_HashKey')) || empty(RY_WEI::get_option('ecpay_HashIV'))) {
                    WC_Admin_Settings::add_error(__('ECPay invoice method failed to enable!', 'ry-woocommerce-ecpay-invoice'));
                    RY_WEI::update_option('enabled_invoice', 'no');
                }
            }

            if (!is_callable('openssl_encrypt') || !is_callable('openssl_decrypt')) {
                WC_Admin_Settings::add_error(__('ECPay invoice method failed to enable!', 'ry-woocommerce-ecpay-invoice')
                    . __('Required PHP function openssl_encrypt and openssl_decrypt.', 'ry-woocommerce-ecpay-invoice'));
                RY_WEI::update_option('enabled_invoice', 'no');
            }
        }

        if (!preg_match('/^[a-z0-9]*$/i', RY_WEI::get_option('order_prefix'))) {
            WC_Admin_Settings::add_error(__('Order no prefix only letters and numbers allowed allowed', 'ry-woocommerce-ecpay-invoice'));
            RY_WEI::update_option('order_prefix', '');
        }

        $delay_days = RY_WEI::get_option('get_delay_days', 0);
        if ($delay_days < 0 || $delay_days > 15) {
            WC_Admin_Settings::add_error(__('Delay day only can between 0 and 15 days.', 'ry-woocommerce-ecpay-invoice'));
            RY_WEI::update_option('get_delay_days', 0);
        }
    }

    public static function output_tools()
    {
        global $hide_save_button;

        $hide_save_button = true;

        if (isset($_POST['ecpay_official_invoice_transfer']) && $_POST['ecpay_official_invoice_transfer'] == 'ecpay_official_invoice_transfer') {
            self::official_invoice_transfer();

            echo '<div class="updated inline"><p>' . __('Data transfer complated.', 'ry-woocommerce-ecpay-invoice') . '</p></div>';
        }

        if (isset($_POST['ecpay_official_invoice_transfer_delete']) && $_POST['ecpay_official_invoice_transfer_delete'] == 'ecpay_official_invoice_transfer_delete') {
            self::official_invoice_transfer_delete();

            echo '<div class="updated inline"><p>' . __('Data transfer complated.', 'ry-woocommerce-ecpay-invoice') . '</p></div>';
        }

        if (isset($_POST['ecpay_official_invoice_delete']) && $_POST['ecpay_official_invoice_delete'] == 'ecpay_official_invoice_delete') {
            self::official_invoice_delete();

            echo '<div class="updated inline"><p>' . __('Data delete complated.', 'ry-woocommerce-ecpay-invoice') . '</p></div>';
        }

        include RY_WEI_PLUGIN_DIR . 'woocommerce/admin/view/html-setting-tools.php';
    }

    protected static function official_invoice_transfer()
    {
        global $wpdb;

        $wpdb->query("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
            SELECT post_id, '_invoice_type', 'personal' FROM $wpdb->postmeta WHERE meta_key = '_billing_invoice_type' AND meta_value = 'p'");
        $wpdb->query("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
            SELECT post_id, '_invoice_type', 'company' FROM $wpdb->postmeta WHERE meta_key = '_billing_invoice_type' AND meta_value = 'c'");
        $wpdb->query("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
            SELECT post_id, '_invoice_type', 'donate' FROM $wpdb->postmeta WHERE meta_key = '_billing_invoice_type' AND meta_value = 'd'");

        $wpdb->query("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
            SELECT post_id, '_invoice_carruer_type', 'none' FROM $wpdb->postmeta WHERE meta_key = '_billing_carruer_type' AND meta_value = '0'");
        $wpdb->query("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
            SELECT post_id, '_invoice_carruer_type', 'ecpay_host' FROM $wpdb->postmeta WHERE meta_key = '_billing_carruer_type' AND meta_value = '1'");
        $wpdb->query("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
            SELECT post_id, '_invoice_carruer_type', 'MOICA' FROM $wpdb->postmeta WHERE meta_key = '_billing_carruer_type' AND meta_value = '2'");
        $wpdb->query("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
            SELECT post_id, '_invoice_carruer_type', 'phone_barcode' FROM $wpdb->postmeta WHERE meta_key = '_billing_carruer_type' AND meta_value = '3'");

        $wpdb->query("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
            SELECT post_id, '_invoice_carruer_no', meta_value FROM $wpdb->postmeta WHERE meta_key = '_billing_carruer_num'");
        $wpdb->query("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
            SELECT post_id, '_invoice_no', meta_value FROM $wpdb->postmeta WHERE meta_key = '_billing_customer_identifier'");
        $wpdb->query("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
            SELECT post_id, '_invoice_donate_no', meta_value FROM $wpdb->postmeta WHERE meta_key = '_billing_love_code'");

        $wpdb->query("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value)
            SELECT post_id, '_invoice_number', meta_value FROM $wpdb->postmeta WHERE `meta_key` = '_ecpay_invoice_number' and `meta_value` != ''
                and post_id in (SELECT post_id FROM $wpdb->postmeta WHERE `meta_key` = '_ecpay_invoice_status' and `meta_value` = '1')");
    }

    protected static function official_invoice_transfer_delete()
    {
        global $wpdb;

        $key_transfer = [
            '_billing_invoice_type' => '_invoice_type',
            '_billing_carruer_type' => '_invoice_carruer_type',
            '_billing_carruer_num' => '_invoice_carruer_no',
            '_billing_customer_identifier' => '_invoice_no',
            '_billing_love_code' => '_invoice_donate_no',
            '_ecpay_invoice_number' => '_invoice_number'
        ];
        foreach ($key_transfer as $from => $to) {
            $wpdb->update($wpdb->postmeta, [
                'meta_key' => $to
            ], [
                'meta_key' => $from
            ]);
        }
        $wpdb->delete($wpdb->postmeta, [
            'meta_key' => '_ecpay_invoice_status'
        ]);
        $wpdb->delete($wpdb->postmeta, [
            'meta_key' => '_invoice_number',
            'meta_value' => ''
        ]);

        $type_transfer = [
            'p' => 'personal',
            'd' => 'company',
            'd' => 'donate'
        ];
        foreach ($type_transfer as $from => $to) {
            $wpdb->update($wpdb->postmeta, [
                'meta_value' => $to
            ], [
                'meta_key' => '_invoice_type',
                'meta_value' => $from
            ]);
        }

        $carruer_type_transfer = [
            '0' => 'none',
            '1' => 'ecpay_host',
            '2' => 'MOICA',
            '3' => 'phone_barcode'
        ];
        foreach ($carruer_type_transfer as $from => $to) {
            $wpdb->update($wpdb->postmeta, [
                'meta_value' => $to
            ], [
                'meta_key' => '_invoice_carruer_type',
                'meta_value' => $from
            ]);
        }
    }

    protected static function official_invoice_delete()
    {
        global $wpdb;

        $keys = [
            '_billing_invoice_type',
            '_billing_carruer_type',
            '_billing_carruer_num',
            '_billing_customer_identifier',
            '_billing_love_code',
            '_ecpay_invoice_number',
            '_ecpay_invoice_status'
        ];
        foreach ($keys as $key) {
            $wpdb->delete($wpdb->postmeta, [
                'meta_key' => $key
            ]);
        }
    }
}

RY_WEI_Invoice_setting::init();
