<?php
/**
 * Settings for barcode payment.
 *
 * @package paynow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings for PayNow Barcode payment gateway
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
		'default'     => __( 'PayNow 超商條碼付款', 'paynow-payment' ),
		'desc_tip'    => true,
	),
	'description' => array(
		'title'       => __( '付款描述', 'paynow-payment' ),
		'type'        => 'textarea',
		'description' => __( '這裡設定使用者選擇這個付款時，看到的描述資訊文字', 'paynow-payment' ),
		'default'     => __( '列印條碼繳費單，前往超商繳費', 'paynow-payment' ),
		'desc_tip'    => true,
	),

);
