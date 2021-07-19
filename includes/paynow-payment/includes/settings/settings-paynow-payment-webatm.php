<?php
/**
 * Settings for webatm payment
 *
 * @package paynow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings for PayNow WebATM payment gateway
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
		'default'     => __( 'PayNow WebATM', 'paynow-payment' ),
		'desc_tip'    => true,
	),
	'description' => array(
		'title'       => __( '付款描述', 'paynow-payment' ),
		'type'        => 'textarea',
		'description' => __( '這裡設定使用者選擇這個付款時，看到的描述資訊文字', 'paynow-payment' ),
		'default'     => __( 'PayNow WebATM，前往網頁ATM櫃員機，使用晶片卡線上轉帳付款', 'paynow-payment' ),
		'desc_tip'    => true,
	),

);
