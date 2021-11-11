<?php
final class RY_WTP_Shipping
{
    public static function init()
    {
        add_filter('wc_order_statuses', [__CLASS__, 'add_order_statuses']);
        add_filter('woocommerce_reports_order_statuses', [__CLASS__, 'add_reports_order_statuses']);
        add_filter('woocommerce_order_is_paid_statuses', [__CLASS__, 'add_order_is_paid_statuses']);
        self::register_order_statuses();

        if (is_admin()) {
        } else {
            //wp_register_script('ry-pro-shipping', RY_WTP_PLUGIN_URL . 'style/js/ry_shipping.js', ['jquery'], RY_WTP_VERSION, true);
        }
    }

    public static function add_order_statuses($order_statuses)
    {
        $order_statuses['wc-ry-transporting'] = _x('Transporting', 'Order status', 'ry-woocommerce-tools-pro');

        return $order_statuses;
    }

    public static function add_reports_order_statuses($order_statuses)
    {
        $order_statuses[] = 'ry-transporting';

        return $order_statuses;
    }

    public static function add_order_is_paid_statuses($statuses)
    {
        $statuses[] = 'ry-transporting';

        return $statuses;
    }

    public static function register_order_statuses()
    {
        register_post_status('wc-ry-transporting', [
            'label' => _x('Transporting', 'Order status', 'ry-woocommerce-tools-pro'),
            'public' => false,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            /* translators: %s: number of orders */
            'label_count' => _n_noop('Transporting <span class="count">(%s)</span>', 'Transporting <span class="count">(%s)</span>', 'ry-woocommerce-tools-pro'),
        ]);
    }
}

//RY_WTP_Shipping::init();
