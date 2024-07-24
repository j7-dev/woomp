<?php
/**
 * Created by PhpStorm.
 * User: Jerry
 * Date: 2017/11/13
 * Time: 上午10:05
 */

defined( 'ABSPATH' ) || exit;

return [
	'enabled'     => [
		'title'   => __( 'Enable/Disable', 'woocommerce' ),
		'type'    => 'checkbox',
		'label'   => __( 'Enable', 'woocommerce' ),
		'default' => 'no',
	],
	'title'       => [
		'title'       => __( 'Title', 'woocommerce' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
		'default'     => __( 'PChomePay', 'woocommerce' ),
		'desc_tip'    => true,
	],
	'description' => [
		'title'       => __( 'Description', 'woocommerce' ),
		'type'        => 'text',
		'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
		'default'     => __( '透過 PChomePay支付連 付款，會連結到 PChomePay支付連 付款頁面。', 'woocommerce' ),
	],
];
