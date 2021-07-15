<?php
$order_statuses = wc_get_order_statuses();
$paid_status = [];
foreach (wc_get_is_paid_statuses() as $status) {
    $paid_status[] = $order_statuses['wc-' . $status];
}
$paid_status = implode(', ', $paid_status);

return [
    [
        'title' => __('Base options', 'ry-woocommerce-ecpay-invoice'),
        'id' => 'base_options',
        'type' => 'title',
    ],
    [
        'title' => __('Enable/Disable', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::$option_prefix . 'enabled_invoice',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Enable ECPay invoice method', 'ry-woocommerce-ecpay-invoice')
    ],
    [
        'title' => __('Debug log', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::$option_prefix . 'invoice_log',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Enable logging', 'ry-woocommerce-ecpay-invoice') . '<br>'
            . sprintf(
                /* translators: %s: Path of log file */
                __('Log ECPay invoice events/message, inside %s', 'ry-woocommerce-ecpay-invoice'),
                '<code>' . WC_Log_Handler_File::get_log_file_path('ry_ecpay_invoice') . '</code>'
            )
    ],
    [
        'title' => __('Order no prefix', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::$option_prefix . 'order_prefix',
        'type' => 'text',
        'desc' => __('The prefix string of order no. Only letters and numbers allowed allowed.', 'ry-woocommerce-ecpay-invoice'),
        'desc_tip' => true
    ],
    [
        'title' => __('Show invoice number', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::$option_prefix . 'show_invoice_number',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Show invoice number in Frontend order list', 'ry-woocommerce-ecpay-invoice')
    ],
    [
        'title' => __('Move billing company', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::$option_prefix . 'move_billing_company',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Move billing company to invoice area', 'ry-woocommerce-ecpay-invoice')
    ],
    [
        'id' => 'base_options',
        'type' => 'sectionend'
    ],
    [
        'title' => __('Invoice options', 'ry-woocommerce-ecpay-invoice'),
        'id' => 'invoice_options',
        'type' => 'title'
    ],
    [
        'title' => __('support paper type', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::$option_prefix . 'support_carruer_type_none',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('You need print invoice and seed to orderer.', 'ry-woocommerce-ecpay-invoice')
    ],
    [
        'title' => __('Get mode', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::$option_prefix . 'get_mode',
        'type' => 'select',
        'default' => 'manual',
        'options' => [
            'manual' => _x('manual', 'get mode', 'ry-woocommerce-ecpay-invoice'),
            'auto_paid' => _x('auto ( when order paid )', 'get mode', 'ry-woocommerce-ecpay-invoice'),
            'auto_completed' => _x('auto ( when order completed )', 'get mode', 'ry-woocommerce-ecpay-invoice')
        ],
        /* translators: %s: paid status */
        'desc' => sprintf(__('Order paid status: %s', 'ry-woocommerce-ecpay-invoice'), $paid_status)
    ],
    [
        'title' => __('Delay get days', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::$option_prefix . 'get_delay_days',
        'type' => 'text',
        'default' => '0',
        'desc' => '如設定為 <strong>0</strong> 天表示立即開立。<br>'
            . '將於達成自動開立的條件下連結至綠界的系統，並設定延遲 N 天後<strong>自動完成</strong>開立發票的相關動作。<br>'
            . '受限於綠界 API 的限制，於設定自動開立到發票完成開立的這段期間中，只能至綠界的管理後台進行待開立發票的取消動作。'
    ],
    [
        'title' => __('Invalid mode', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::$option_prefix . 'invalid_mode',
        'type' => 'select',
        'default' => 'manual',
        'options' => [
            'manual' => _x('manual', 'invalid mode', 'ry-woocommerce-ecpay-invoice'),
            'auto_cancell' => _x('auto ( when order status cancelled OR refunded )', 'invalid mode', 'ry-woocommerce-ecpay-invoice')
        ]
    ],
    [
        'id' => 'invoice_options',
        'type' => 'sectionend'
    ],
    [
        'title' => __('API credentials', 'ry-woocommerce-ecpay-invoice'),
        'id' => 'api_options',
        'type' => 'title'
    ],
    [
        'title' => __('ECPay invoice sandbox', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::$option_prefix . 'ecpay_testmode',
        'type' => 'checkbox',
        'default' => 'yes',
        'desc' => __('Enable ECPay invoice sandbox', 'ry-woocommerce-ecpay-invoice')
    ],
    [
        'title' => __('MerchantID', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::$option_prefix . 'ecpay_MerchantID',
        'type' => 'text',
        'default' => ''
    ],
    [
        'title' => __('HashKey', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::$option_prefix . 'ecpay_HashKey',
        'type' => 'text',
        'default' => ''
    ],
    [
        'title' => __('HashIV', 'ry-woocommerce-ecpay-invoice'),
        'id' => RY_WEI::$option_prefix . 'ecpay_HashIV',
        'type' => 'text',
        'default' => ''
    ],
    [
        'id' => 'api_options',
        'type' => 'sectionend'
    ]
];
