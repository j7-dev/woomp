<?php
final class RY_WTP
{
    public static $options = [];
    public static $option_prefix = 'RY_WTP_';

    private static $initiated = false;
    private static $activate_status = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            load_plugin_textdomain('ry-woocommerce-tools-pro', false, plugin_basename(dirname(RY_WTP_PLUGIN_BASENAME)) . '/languages');

            if (!defined('WC_VERSION')) {
                return;
            }

            if (!defined('RY_WT_VERSION')) {
                add_action('admin_notices', [__CLASS__, 'need_ry_woocommerce_tools']);
                return;
            }

            self::$activate_status = self::valid_key();

            include_once RY_WTP_PLUGIN_DIR . 'include/class.updater.php';
            include_once RY_WTP_PLUGIN_DIR . 'include/class.link-server.php';

            include_once RY_WTP_PLUGIN_DIR . 'class.ry-wt-p.update.php';
            RY_WTP_update::update();

            if (is_admin()) {
                include_once RY_WTP_PLUGIN_DIR . 'class.ry-wt-p.admin.php';
            }

            if (self::$activate_status) {
                include_once RY_WTP_PLUGIN_DIR . 'include/class.cron.php';

                // 綠界金流
                if ('yes' == RY_WT::get_option('enabled_ecpay_gateway', 'no')) {
                    include_once RY_WTP_PLUGIN_DIR . 'woocommerce/gateways/ecpay/ecpay-gateway.php';
                }
                // 綠界物流
                if ('yes' == RY_WT::get_option('enabled_ecpay_shipping', 'no')) {
                    include_once RY_WTP_PLUGIN_DIR . 'woocommerce/shipping/ecpay/ecpay-shipping.php';
                }

                // 藍新金流
                if ('yes' == RY_WT::get_option('enabled_newebpay_gateway', 'no')) {
                    include_once RY_WTP_PLUGIN_DIR . 'woocommerce/gateways/newebpay/newebpay-gateway.php';
                }
                // 藍新物流
                if ('yes' == RY_WT::get_option('enabled_newebpay_shipping', 'no')) {
                    include_once RY_WTP_PLUGIN_DIR . 'woocommerce/shipping/newebpay/newebpay-shipping.php';
                }

                // 速買配金流
                if ('yes' == RY_WT::get_option('enabled_smilepay_gateway', 'no')) {
                    include_once RY_WTP_PLUGIN_DIR . 'woocommerce/gateways/smilepay/smilepay-gateway.php';
                }
                // 速買配物流
                if ('yes' == RY_WT::get_option('enabled_smilepay_shipping', 'no')) {
                    include_once RY_WTP_PLUGIN_DIR . 'woocommerce/shipping/smilepay/smilepay-shipping.php';
                }
            }
        }
    }

    public static function need_ry_woocommerce_tools()
    {
        $message = sprintf(
            /* translators: %s: Name of this plugin */
            __('<strong>%s</strong> is inactive. It require RY WooCommerce Tools.', 'ry-woocommerce-tools-pro'),
            __('RY WooCommerce Tools Pro', 'ry-woocommerce-tools-pro')
        );
        printf('<div class="error"><p>%s</p></div>', $message);
    }

    public static function check_expire()
    {
        $json = RY_WTP_link_server::expire_data();
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
