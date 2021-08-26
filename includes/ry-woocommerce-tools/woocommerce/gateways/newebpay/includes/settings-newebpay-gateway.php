<?php
return [
    [
        'title' => __('藍新金流設定', 'ry-woocommerce-tools'),
        'id' => 'base_options',
        'type' => 'title',
    ],
    //[
    //    'title' => __('Enable/Disable', 'woocommerce'),
    //    'id' => RY_WT::$option_prefix . 'newebpay_gateway',
    //    'type' => 'checkbox',
    //    'default' => 'no',
    //    'desc' => __('Enable NewebPay gateway method', 'ry-woocommerce-tools')
    //],
    [
        'title' => __('Debug log', 'woocommerce'),
        'id' => RY_WT::$option_prefix . 'newebpay_gateway_log',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Enable logging', 'woocommerce') . '<br>'
            . sprintf(
                /* translators: %s: Path of log file */
                __('Log NewebPay gateway events/message, inside %s', 'ry-woocommerce-tools'),
                '<code>' . WC_Log_Handler_File::get_log_file_path('ry_newebpay_gateway') . '</code>'
            )
    ],
    [
        'title' => __('Order no prefix', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'newebpay_gateway_order_prefix',
        'type' => 'text',
        'desc' => __('The prefix string of order no. Only letters and numbers allowed allowed.', 'ry-woocommerce-tools'),
        'desc_tip' => true
    ],
    [
        'id' => 'base_options',
        'type' => 'sectionend'
    ],
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
    ],
    [
        'title' => __('API credentials', 'ry-woocommerce-tools'),
        'id' => 'api_options',
        'type' => 'title'
    ],
    [
        'title' => __('NewebPay gateway sandbox', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'newebpay_gateway_testmode',
        'type' => 'checkbox',
        'default' => 'yes',
        'desc' => __('Enable NewebPay gateway sandbox', 'ry-woocommerce-tools')
    ],
    [
        'title' => __('MerchantID', 'NewebPay', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'newebpay_gateway_MerchantID',
        'type' => 'text',
        'default' => ''
    ],
    [
        'title' => __('HashKey', 'NewebPay', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'newebpay_gateway_HashKey',
        'type' => 'text',
        'default' => ''
    ],
    [
        'title' => __('HashIV', 'NewebPay', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'newebpay_gateway_HashIV',
        'type' => 'text',
        'default' => ''
    ],
    [
        'id' => 'api_options',
        'type' => 'sectionend'
    ]
];