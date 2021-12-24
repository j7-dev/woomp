<?php
return [
    [
        'title' => __('藍新物流設定', 'ry-woocommerce-tools'),
        'id' => 'base_options',
        'type' => 'title',
        'desc' => __('Because NewebPay limit, the shipping note no or shipping status can not show in site admin.', 'ry-woocommerce-tools')
    ],
    //[
    //    'title' => __('Enable/Disable', 'woocommerce'),
    //    'id' => RY_WT::$option_prefix . 'newebpay_shipping',
    //    'type' => 'checkbox',
    //    'default' => 'no',
    //    'desc' => __('Enable NewebPay shipping method', 'ry-woocommerce-tools')
    //],
    //[
    //    'title' => __('cvs remove billing address', 'ry-woocommerce-tools-pro'),
    //    'id' => RY_WTP::$option_prefix . 'newebpay_cvs_billing_address',
    //    'type' => 'checkbox',
    //    'default' => 'no',
    //    'desc' => __('Remove billing address when shipping mode is cvs.', 'ry-woocommerce-tools-pro') . '<br>'
    //        . __('The billing address still will show in order details.', 'ry-woocommerce-tools-pro')
    //],
    [
        'id' => 'base_options',
        'type' => 'sectionend'
    ]
];
