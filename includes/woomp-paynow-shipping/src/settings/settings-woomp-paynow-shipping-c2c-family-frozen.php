<?php
/**
 * PayNow c2c family frozen shipping method settings file.
 *
 * @package woomp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings for Paynow Shipping method
 */
$settings = array(

	'title'                    => array(
		'title'       => __( 'Title', 'woomp' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'woomp' ),
		'default'     => __( 'PayNow Shipping C2C Family Frozen', 'woomp' ),
		'desc_tip'    => true,
	),
	'description'              => array(
		'title'       => __( 'Description', 'woomp' ),
		'type'        => 'textarea',
		'description' => __( 'This controls the description which the user sees during checkout.', 'woomp' ),
		'desc_tip'    => true,
	),
	'measurement'              => array(
		'title'   => __( 'Shipping Measurement', 'woomp' ),
		'type'    => 'select',
		'class'   => 'wc-enhanced-select',
		'default' => '',
		'options' => array(
			's60'  => __( 's60', 'woomp' ),
			's105' => __( 's105', 'woomp' ),
		),
	),
	'cost'                     => array(
		'title'   => __( 'Shipping Cost', 'woomp' ),
		'type'    => 'number',
		'default' => 0,
		'min'     => 0,
		'step'    => 1,
	),
	'free_shipping_requires'   => array(
		'title'   => __( 'Free shipping requires', 'woomp' ),
		'type'    => 'select',
		'class'   => 'wc-enhanced-select',
		'default' => '',
		'options' => array(
			''           => __( 'N/A', 'woomp' ),
			'min_amount' => __( 'A minimum order amount', 'woomp' ),
		),
	),
	'free_shipping_min_amount' => array(
		'title'       => __( 'Minimum order amount for free shipping', 'woomp' ),
		'type'        => 'price',
		'default'     => 0,
		'placeholder' => wc_format_localized_price( 0 ),
		'description' => __( 'Users will need to spend this amount to get free shipping.', 'woomp' ),
		'desc_tip'    => true,
	),

);

$shipping_classes = WC()->shipping->get_shipping_classes();

if ( ! empty( $shipping_classes ) ) {
	$settings['class_available'] = array(
		'title'       => __( 'Shipping available', 'woomp' ),
		'type'        => 'title',
		'default'     => '',
		/* translators: %s: shipping class setting url */
		'description' => sprintf( __( 'These shipping available based on the <a href="%s">product shipping class</a>.', 'woomp' ), admin_url( 'admin.php?page=wc-settings&tab=shipping&section=classes' ) ),
	);
	foreach ( $shipping_classes as $shipping_class ) {
		if ( ! isset( $shipping_class->term_id ) ) {
			continue;
		}
		$settings[ 'class_available_' . $shipping_class->term_id ] = array(
			/* translators: %s: shipping class name */
			'title'   => sprintf( __( '"%s" available', 'woomp' ), esc_html( $shipping_class->name ) ),
			'type'    => 'checkbox',
			'default' => $this->get_option( 'class_available_' . $shipping_class->term_id, 'yes' ),
		);
		$settings[ 'class_cost_' . $shipping_class->term_id ]      = array(
			/* translators: %s: shipping class name */
			'title'             => sprintf( __( '"%s" shipping class cost', 'woocommerce' ), esc_html( $shipping_class->name ) ),
			'type'              => 'text',
			'placeholder'       => __( 'N/A', 'woocommerce' ),
			'description'       => $cost_desc,
			'default'           => $this->get_option( 'class_cost_' . $shipping_class->slug ),
			'desc_tip'          => true,
			'sanitize_callback' => array( $this, 'sanitize_cost' ),
		);
	}
}

return $settings;