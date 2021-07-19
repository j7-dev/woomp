<?php
/**
 * Settings for famiport payment.
 *
 * @package paynow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings for PayNow Famiport Payment Gateway
 */
return array(

	'enabled'     => array(
		'title'   => __( 'Enable/Disable', 'paynow-payment' ),
		'type'    => 'checkbox',
		'label'   => __( 'Enable', 'paynow-payment' ),
		'default' => 'no',
	),
	'title'       => array(
		'title'       => __( 'Title', 'paynow-payment' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'paynow-payment' ),
		'default'     => __( 'Paynow FamiPort Payment', 'paynow-payment' ),
		'desc_tip'    => true,
	),
	'description' => array(
		'title'       => __( 'Description', 'paynow-payment' ),
		'type'        => 'textarea',
		'description' => __( 'This controls the description which the user sees during checkout.', 'paynow-payment' ),
		'desc_tip'    => true,
	),

);
