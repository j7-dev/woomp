<?php
/**
 * Settings for Virtual Account payment.
 *
 * @package paynow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings for PayNow Virtual Account Payment Gateway
 */
return array(

	'enabled'     => array(
		'title'   => __( '啟用', 'paynow-payment' ),
		'type'    => 'checkbox',
		'label'   => __( '勾選，啟用這個付款', 'paynow-payment' ),
		'default' => 'no',
	),
	'title'       => array(
		'title'       => __( '付款標題', 'paynow-payment' ),
		'type'        => 'text',
		'description' => __( '這裡設定使用者付款時看到的付款網關名稱.', 'paynow-payment' ),
		'default'     => __( 'PayNow 虛擬帳號付款', 'paynow-payment' ),
		'desc_tip'    => true,
	),
	'description' => array(
		'title'       => __( 'Description', 'paynow-payment' ),
		'type'        => 'textarea',
		'description' => __( '這裡設定使用者選擇這個付款時，看到的描述資訊文字', 'paynow-payment' ),
		'default'     => __( 'PayNow 虛擬帳號付款，取得虛擬帳號，前往ATM櫃員機轉帳付款', 'paynow-payment' ),
		'desc_tip'    => true,
	),
);
