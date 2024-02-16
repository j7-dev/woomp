<?php

if (wc_string_to_bool(get_option('woocommerce_pchomepay_enabled'))) {
    add_action('plugins_loaded', 'pchomepay_gateway_init', 0);

    function pchomepay_gateway_init()
    {
        // Make sure WooCommerce is setted.
        if (!class_exists('WC_Payment_Gateway')) {
            return;
        }

        require_once WOOMP_PLUGIN_DIR . 'includes/PChomePay-Cart-for-WooCommerce/includes/PChomePayClient.php';
        require_once WOOMP_PLUGIN_DIR . 'includes/PChomePay-Cart-for-WooCommerce/includes/PChomePayGateway.php';
        require_once WOOMP_PLUGIN_DIR . 'includes/PChomePay-Cart-for-WooCommerce/includes/PChomePayPIGateway.php';

        function add_pchomepay_gateway_class($methods)
        {
            $methods[  ] = 'WC_Gateway_PChomePay';
            $methods[  ] = 'WC_PI_Gateway_PChomePay';
            return $methods;
        }

        add_filter('woocommerce_payment_gateways', 'add_pchomepay_gateway_class');

        function customize_order_received_text($text, $order)
        {
            return WC_Gateway_PChomePay::$customize_order_received_text;
        }

        //add_filter( 'woocommerce_thankyou_order_received_text', 'customize_order_received_text', 10, 2 );
    }

    // 審單功能
    add_action('woocommerce_order_actions', 'pchomepay_audit_order_action');

    function pchomepay_audit_order_action($actions)
    {
        global $theorder;

        // bail if the order has been paid for or this action has been run
        if ($theorder->get_status() != 'awaiting' || $theorder->payment_method != 'pchomepay') {
            return $actions;
        }

        $actions[ 'wc_order_pass' ] = __('PChomePay - 訂單過單', 'woocommerce');
        $actions[ 'wc_order_deny' ] = __('PChomePay - 訂單取消', 'woocommerce');
        return $actions;
    }

    // 過單
    add_action('woocommerce_order_action_wc_order_pass', 'pchomepay_audit_order_pass');

    function pchomepay_audit_order_pass($order)
    {
        require_once WOOMP_PLUGIN_DIR . 'includes/PChomePay-Cart-for-WooCommerce/includes/PChomePayClient.php';
        require_once WOOMP_PLUGIN_DIR . 'includes/PChomePay-Cart-for-WooCommerce/includes/PChomePayGateway.php';

        $pchomepayGatway = new WC_Gateway_PChomePay();
        $result          = $pchomepayGatway->process_audit($order->get_id(), 'PASS');

        if (!$result) {
            WC_Admin_Meta_Boxes::add_error('嘗試使用付款閘道 API 審單時發生錯誤!');
        }
    }

    // 不過單
    add_action('woocommerce_order_action_wc_order_deny', 'pchomepay_audit_order_deny');

    function pchomepay_audit_order_deny($order)
    {
        require_once WOOMP_PLUGIN_DIR . 'includes/PChomePay-Cart-for-WooCommerce/includes/PChomePayClient.php';
        require_once WOOMP_PLUGIN_DIR . 'includes/PChomePay-Cart-for-WooCommerce/includes/PChomePayGateway.php';

        $pchomepayGatway = new WC_Gateway_PChomePay();
        $result          = $pchomepayGatway->process_audit($order->get_id(), 'DENY');

        if (!$result) {
            WC_Admin_Meta_Boxes::add_error('嘗試使用付款閘道 API 審單時發生錯誤!');
        }
    }

    // Add to list of WC Order statuses
    add_action('init', 'register_awaiting_audit_order_status');

    function register_awaiting_audit_order_status()
    {
        register_post_status(
            'wc-awaiting',
            array(
                'label'                     => '等待審單',
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop('等待審單 <span class="count">(%s)</span>', '等待審單 <span class="count">(%s)</span>'),
            )
        );
    }

    add_filter('wc_order_statuses', 'add_awaiting_audit_order_statuses');

    function add_awaiting_audit_order_statuses($order_statuses)
    {
        $new_order_statuses = array();
        // add new order status after processing
        foreach ($order_statuses as $key => $status) {
            $new_order_statuses[ $key ] = $status;
            if ('wc-processing' === $key) {
                $new_order_statuses[ 'wc-awaiting' ] = '等待審單';
            }
        }
        return $new_order_statuses;
    }

    // Add to list of WC Order statuses
    add_action('init', 'register_awaiting_pchomepay_audit_order_status');

    function register_awaiting_pchomepay_audit_order_status()
    {
        register_post_status(
            'wc-awaitingforpcpay',
            array(
                'label'                     => '等待支付連審單',
                'public'                    => true,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop('等待支付連審單 <span class="count">(%s)</span>', '等待支付連審單 <span class="count">(%s)</span>'),
            )
        );
    }

    add_filter('wc_order_statuses', 'add_awaiting_pchomepay_audit_order_statuses');

    function add_awaiting_pchomepay_audit_order_statuses($order_statuses)
    {
        $new_order_statuses = array();
        // add new order status after processing
        foreach ($order_statuses as $key => $status) {
            $new_order_statuses[ $key ] = $status;
            if ('wc-processing' === $key) {
                $new_order_statuses[ 'wc-awaitingforpcpay' ] = '等待支付連審單';
            }
        }
        return $new_order_statuses;
    }

    // 顧客訂單頁面 7-11物流歷程查詢
    add_filter('woocommerce_my_account_my_orders_actions', 'add_my_account_my_orders_custom_action', 10, 2);
    function add_my_account_my_orders_custom_action($actions, $order)
    {
        if ($order->get_meta('_pchomepay_paytype') == 'IPL7' && $order->payment_method == 'pchomepay') {

            require_once WOOMP_PLUGIN_DIR . 'includes/PChomePay-Cart-for-WooCommerce/includes/PChomePayClient.php';
            require_once WOOMP_PLUGIN_DIR . 'includes/PChomePay-Cart-for-WooCommerce/includes/PChomePayGateway.php';

            $pchomepayGateway = new WC_Gateway_PChomePay();
            $url              = $pchomepayGateway->process_query711_history_page($order->get_id());

            $action_slug             = 'pchomepay_ipl7';
            $actions[ $action_slug ] = array(
                'url'  => $url,
                'name' => '物流歷程',
            );
        }
        return $actions;
    }

    // Jquery script
    add_action('woocommerce_after_account_orders', 'action_after_account_orders_js');
    function action_after_account_orders_js()
    {
        $action_slug = 'pchomepay_ipl7';
        ?>
<script>
jQuery(function($) {
	$('a.<?php echo $action_slug; ?>').each(function() {
		$(this).attr('target', '_blank');
	})
});
</script>
<?php
}

    // The column content by row
    //add_action( 'manage_shop_order_posts_custom_column', 'add_custom_action_in_column_contents', 50, 2 );
    function add_custom_action_in_column_contents($column, $post_id)
    {

        $order = wc_get_order($post_id);

        if (in_array($order->payment_method, array('pchomepay', 'pchomepay_pi'))) {
            if ($column == 'order_number') {

                if ($customer_phone = $order->get_billing_phone()) {
                    echo '<p><a href="tel:' . $customer_phone . '"><span class="dashicons dashicons-phone"></span> ' . $customer_phone . '</a></strong></p>';
                }

                if ($customer_email = $order->get_billing_email()) {
                    echo '<p><a href="mailto:' . $customer_email . '"><span class="dashicons dashicons-email"></span> ' . $customer_email . '</a></strong></p>';
                }

                if ($order->get_meta('_pchomepay_paytype') == 'IPL7') {

                    require_once WOOMP_PLUGIN_DIR . 'includes/PChomePay-Cart-for-WooCommerce/includes/PChomePayClient.php';
                    require_once WOOMP_PLUGIN_DIR . 'includes/PChomePay-Cart-for-WooCommerce/includes/PChomePayGateway.php';

                    $pchomepayGateway = new WC_Gateway_PChomePay();
                    $url              = $pchomepayGateway->process_query711_history_page($order->get_order_number());
                    $slug             = 'pchomepay_ipl7';
                    // Output the button
                    echo '<p><a class="' . $slug . '" href="' . $url . '"><span class="dashicons dashicons-external ' . $slug . '"></span>查詢物流歷程</a></strong></p>';
                }
            }
        }
    }

    // The CSS styling
    add_action('admin_head', 'add_custom_action_button_css');
    function add_custom_action_button_css()
    {
        $action_slug = 'pchomepay_ipl7';

        ?>
<script>
jQuery(function($) {
	$('a.<?php echo $action_slug; ?>').each(function() {
		$(this).attr('target', '_blank');
	})
});
</script>
<?php

        echo '<style>.wc-action-button-' . $action_slug . '::after { font-family: woocommerce !important; content: "\e029" !important; }</style>';
    }
}