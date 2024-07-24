<?php
/**
 * Settings for credit card payment.
 *
 * @package paynow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings for PayNow credit card payment gateway
 */
return [

	'enabled'     => [
		'title'   => __( '啟用', 'paynow-payment' ),
		'type'    => 'checkbox',
		'label'   => __( '勾選，啟用這個付款', 'paynow-payment' ),
		'default' => 'no',
	],
	'title'       => [
		'title'       => __( '付款標題', 'paynow-payment' ),
		'type'        => 'text',
		'description' => __( '這裡設定使用者付款時看到的付款網關名稱.', 'paynow-payment' ),
		'default'     => __( 'PayNow 信用卡線上付款', 'paynow-payment' ),
		'desc_tip'    => true,
	],
	'description' => [
		'title'       => __( '付款描述', 'paynow-payment' ),
		'type'        => 'textarea',
		'description' => __( '這裡設定使用者選擇這個付款時，看到的描述資訊文字', 'paynow-payment' ),
		'desc_tip'    => true,
	],

];
