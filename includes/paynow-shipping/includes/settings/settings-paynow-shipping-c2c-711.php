<?php
/**
 * PayNow Shipping c2c 7-11 setting array.
 *
 * @package paynow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings for PayNow Shipping c2c 7-11
 */
return array(
	'title'                    => array(
		'title'       => __( 'Title', 'paynow-shipping' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'paynow-shipping' ),
		'default'     => __( 'PayNow Shipping C2C 7-11', 'paynow-shipping' ),
		'desc_tip'    => true,
	),
	'description'              => array(
		'title'       => __( 'Description', 'paynow-shipping' ),
		'type'        => 'textarea',
		'description' => __( 'This controls the description which the user sees during checkout.', 'paynow-shipping' ),
		'desc_tip'    => true,
	),
	'cost'                     => array(
		'title'   => __( 'Shipping Cost', 'paynow-shipping' ),
		'type'    => 'number',
		'default' => 0,
		'min'     => 0,
		'step'    => 1,
	),
	'free_shipping_requires'   => array(
		'title'   => __( 'Free shipping requires', 'paynow-shipping' ),
		'type'    => 'select',
		'class'   => 'wc-enhanced-select',
		'default' => '',
		'options' => array(
			''           => __( 'N/A', 'paynow-shipping' ),
			'min_amount' => __( 'A minimum order amount', 'paynow-shipping' ),
		),
	),
	'free_shipping_min_amount' => array(
		'title'       => __( 'Minimum order amount for free shipping', 'paynow-shipping' ),
		'type'        => 'price',
		'default'     => 0,
		'placeholder' => wc_format_localized_price( 0 ),
		'description' => __( 'Users will need to spend this amount to get free shipping.', 'paynow-shipping' ),
		'desc_tip'    => true,
	),
);
