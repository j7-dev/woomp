<?php
final class RY_WEI
{
    public static $options = [];
    public static $option_prefix = 'RY_WEI_';

    private static $initiated = false;
    private static $activate_status = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            load_plugin_textdomain('ry-woocommerce-ecpay-invoice', false, plugin_basename(dirname(RY_WEI_PLUGIN_BASENAME)) . '/languages');

            if (!defined('WC_VERSION')) {
                return;
            }

            self::$activate_status = self::valid_key();

            include_once RY_WEI_PLUGIN_DIR . 'include/class.updater.php';
            include_once RY_WEI_PLUGIN_DIR . 'include/class.link-server.php';

            include_once RY_WEI_PLUGIN_DIR . 'class.ry-wei.update.php';
            RY_WEI_update::update();

            if (is_admin()) {
                include_once RY_WEI_PLUGIN_DIR . 'class.ry-wei.admin.php';
            }

            include_once RY_WEI_PLUGIN_DIR . 'include/class.cron.php';
            include_once RY_WEI_PLUGIN_DIR . 'woocommerce/settings/class-settings.invoice.php';

            if ('yes' == self::get_option('enabled_invoice', 'no')) {
                include_once RY_WEI_PLUGIN_DIR . 'woocommerce/class.invoice.php';
            }
        }
    }

    public static function check_expire()
    {
        $json = RY_WEI_link_server::expire_data();
        if (is_array($json) && isset($json['data'])) {
            self::update_option('pro_Data', $json['data']);
        } else {
            wp_unschedule_hook(self::$option_prefix . 'check_expire');
        }
    }

    private static function valid_key()
    {
        $pro_data = self::get_option('pro_Data');
        if (is_array($pro_data) && isset($pro_data['secret'])) {
            if (hash_equals($pro_data['secret'], hash($pro_data['type'], $pro_data['expire']))) {
                return true;
            }
        }
        return false;
    }

    public static function get_option($option, $default = false)
    {
        return get_option(self::$option_prefix . $option, $default);
    }

    public static function update_option($option, $value)
    {
        return update_option(self::$option_prefix . $option, $value);
    }

    public static function delete_option($option)
    {
        return delete_option(self::$option_prefix . $option);
    }

    public static function plugin_activation()
    {
    }

    public static function plugin_deactivation()
    {
        wp_unschedule_hook(self::$option_prefix . 'check_expire');
        wp_unschedule_hook(self::$option_prefix . 'check_update');
    }
}
