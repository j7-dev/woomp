<?php
final class RY_WEI_admin
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            if (!defined('RY_WT_VERSION')) {
                add_filter('woocommerce_get_settings_pages', [__CLASS__, 'get_settings_page']);
            }
            add_filter('woocommerce_get_sections_rytools', [__CLASS__, 'add_sections'], 12);

            add_filter('woocommerce_get_settings_rytools', [__CLASS__, 'add_setting'], 10, 2);
            add_action('woocommerce_update_options_rytools_ry_key', [__CLASS__, 'activate_key']);
        }
    }

    public static function get_settings_page($settings)
    {
        $settings[] = include RY_WEI_PLUGIN_DIR . 'woocommerce/settings/class-settings-ry-wei.php';

        return $settings;
    }

    public static function add_sections($sections)
    {
        unset($sections['ry_key']);
        //$sections['ry_key'] = __('License key', 'ry-woocommerce-ecpay-invoice');

        return $sections;
    }

    public static function add_setting($settings, $current_section)
    {
        if ($current_section == 'ry_key') {
            add_action('woocommerce_admin_field_rywei_version_info', [__CLASS__, 'show_version_info']);
            if (empty($settings)) {
                $settings = [];
            }
            $settings = array_merge($settings, include RY_WEI_PLUGIN_DIR . 'woocommerce/settings/settings-ry-key.php');

            $pro_data = RY_WEI::get_option('pro_Data');
            if (is_array($pro_data) && isset($pro_data['expire'])) {
                foreach ($settings as $key => $setting) {
                    if (isset($setting['id']) && $setting['id'] == RY_WEI::$option_prefix . 'pro_Key') {
                        $settings[$key]['desc'] = sprintf(
                            /* translators: %s: Expiration date of pro license */
                            __('License Expiration Date %s', 'ry-woocommerce-ecpay-invoice'),
                            date_i18n(get_option('date_format'), $pro_data['expire'])
                        );
                    }
                }
            }
        }
        return $settings;
    }

    public static function show_version_info($value)
    {
        $version = RY_WEI::get_option('version');
        $version_info = RY_WEI_link_server::check_version();

        include RY_WEI_PLUGIN_DIR . 'woocommerce/admin/view/html-version-info.php';
    }

    public static function activate_key()
    {
        if (!empty(RY_WEI::get_option('pro_Key'))) {
            $json = RY_WEI_link_server::activate_key();

            if ($json === false) {
                WC_Admin_Settings::add_error(__('RY WooCommerce ECPay Invoice', 'ry-woocommerce-ecpay-invoice') . ': '
                    . __('Connect license server failed!', 'ry-woocommerce-ecpay-invoice'));
            } else {
                if (is_array($json)) {
                    if (empty($json['data'])) {
                        WC_Admin_Settings::add_error(__('RY WooCommerce ECPay Invoice', 'ry-woocommerce-ecpay-invoice') . ': '
                            . sprintf(
                                /* translators: %s: Error message */
                                __('Verification error: %s', 'ry-woocommerce-ecpay-invoice'),
                                __($json['error'], 'ry-woocommerce-ecpay-invoice')
                            ));

                        /* Error message list. For make .pot */
                        __('Unknown key', 'ry-woocommerce-ecpay-invoice');
                        __('Locked key', 'ry-woocommerce-ecpay-invoice');
                        __('Unknown target url', 'ry-woocommerce-ecpay-invoice');
                        __('Used key', 'ry-woocommerce-ecpay-invoice');
                        __('Is tried', 'ry-woocommerce-ecpay-invoice');
                    } else {
                        RY_WEI::update_option('pro_Data', $json['data']);
                        return true;
                    }
                } else {
                    WC_Admin_Settings::add_error(__('RY WooCommerce ECPay Invoice', 'ry-woocommerce-ecpay-invoice') . ': '
                    . __('Connect license server failed!', 'ry-woocommerce-ecpay-invoice'));
                }
            }
        }

        RY_WEI::check_expire();
        RY_WEI::update_option('pro_Key', '');
    }
}

RY_WEI_admin::init();
