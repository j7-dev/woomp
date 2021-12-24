<?php
return [
    [
        'title' => __('速買配物流設定', 'ry-woocommerce-tools'),
        'id' => 'base_options',
        'type' => 'title'
    ],
    //[
    //    'title' => __('Enable/Disable', 'woocommerce'),
    //    'id' => RY_WT::$option_prefix . 'smilepay_shipping',
    //    'type' => 'checkbox',
    //    'default' => 'no',
    //    'desc' => __('Enable SmilePay shipping method', 'ry-woocommerce-tools')
    //],
    [
        'title' => __('Log status change', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'smilepay_shipping_log_status_change',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Log status change at order notes.', 'ry-woocommerce-tools')
    ],
    [
        'title' => __('Auto get shipping payment no', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'smilepay_shipping_auto_get_no',
        'type' => 'checkbox',
        'default' => 'yes',
        'desc' => __('Auto get shipping payment no when order status is change to processing.', 'ry-woocommerce-tools')
    ],
    //[
    //    'title' => __('Auto get with scheduler action', 'ry-woocommerce-tools-pro'),
    //    'id' => RY_WTP::$option_prefix . 'smilepay_shipping_auto_with_scheduler',
    //    'type' => 'checkbox',
    //    'default' => 'no',
    //    'desc' => __('Get shipping payment no use scheduler action.', 'ry-woocommerce-tools-pro')
    //],
    [
        'title' => __('Keep shipping phone', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'keep_shipping_phone',
        'type' => 'checkbox',
        'default' => 'no',
        'desc' => __('Always show shipping phone field in checkout form.', 'ry-woocommerce-tools')
    ],
    [
        'title' => __('Auto completed order', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'smilepay_shipping_auto_completed',
        'type' => 'checkbox',
        'default' => 'yes',
        'desc' => __('Auto completed order when user getted products.', 'ry-woocommerce-tools')
    ],
    //[
    //    'title' => __('cvs remove billing address', 'ry-woocommerce-tools-pro'),
    //    'id' => RY_WTP::$option_prefix . 'smilepay_cvs_billing_address',
    //    'type' => 'checkbox',
    //    'default' => 'no',
    //    'desc' => __('Remove billing address when shipping mode is cvs.', 'ry-woocommerce-tools-pro') . '<br>'
    //        . __('The billing address still will show in order details.', 'ry-woocommerce-tools-pro')
    //],
    [
        'id' => 'base_options',
        'type' => 'sectionend'
    ],
    [
        'title' => __('Shipping note options', 'ry-woocommerce-tools'),
        'id' => 'note_options',
        'type' => 'title'
    ],
    [
        'title' => __('shipping item name', 'ry-woocommerce-tools-pro'),
        'id' => RY_WT::$option_prefix . 'shipping_item_name',
        'type' => 'text',
        'default' => '',
        'desc' => __('If empty use the first product name.', 'ry-woocommerce-tools-pro'),
        'desc_tip' => true
    ],
    [
        'title' => __('Cvs shipping type', 'ry-woocommerce-tools'),
        'id' => RY_WT::$option_prefix . 'smilepay_shipping_cvs_type',
        'type' => 'select',
        'default' => 'C2C',
        'options' => [
            'C2C' => _x('C2C', 'Cvs type', 'ry-woocommerce-tools')
        ]
    ],
    [
        'id' => 'note_options',
        'type' => 'sectionend'
    ]
];
