<?php
/**
 * PayNow Shipping c2c Hi-Life setting array.
 *
 * @package paynow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings for PayNow Shipping c2c Hi-Life
 */
$settings = [
	'title'                    => [
		'title'       => __( 'Title', 'paynow-shipping' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'paynow-shipping' ),
		'default'     => __( 'PayNow Shipping C2C Hi-Life', 'paynow-shipping' ),
		'desc_tip'    => true,
	],
	'description'              => [
		'title'       => __( 'Description', 'paynow-shipping' ),
		'type'        => 'textarea',
		'description' => __( 'This controls the description which the user sees during checkout.', 'paynow-shipping' ),
		'desc_tip'    => true,
	],
	'cost'                     => [
		'title'   => __( 'Shipping Cost', 'paynow-shipping' ),
		'type'    => 'number',
		'default' => 0,
		'min'     => 0,
		'step'    => 1,
	],
	'free_shipping_requires'   => [
		'title'   => __( 'Free shipping requires', 'paynow-shipping' ),
		'type'    => 'select',
		'class'   => 'wc-enhanced-select',
		'default' => '',
		'options' => [
			''           => __( 'N/A', 'paynow-shipping' ),
			'min_amount' => __( 'A minimum order amount', 'paynow-shipping' ),
		],
	],
	'free_shipping_min_amount' => [
		'title'       => __( 'Minimum order amount for free shipping', 'paynow-shipping' ),
		'type'        => 'price',
		'default'     => 0,
		'placeholder' => wc_format_localized_price( 0 ),
		'description' => __( 'Users will need to spend this amount to get free shipping.', 'paynow-shipping' ),
		'desc_tip'    => true,
	],
];


$shipping_classes = WC()->shipping->get_shipping_classes();
$cost_desc        = __( 'Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.', 'woocommerce' ) . '<br/><br/>' . __( 'Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent="10" min_fee="20" max_fee=""]</code> for percentage based fees.', 'woocommerce' );


if ( ! empty( $shipping_classes ) ) {
	$settings['class_available'] = [
		'title'       => __( 'Shipping available', 'woomp' ),
		'type'        => 'title',
		'default'     => '',
		/* translators: %s: shipping class setting url */
		'description' => sprintf( __( 'These shipping available based on the <a href="%s">product shipping class</a>.', 'woomp' ), admin_url( 'admin.php?page=wc-settings&tab=shipping&section=classes' ) ),
	];
	foreach ( $shipping_classes as $shipping_class ) {
		if ( ! isset( $shipping_class->term_id ) ) {
			continue;
		}
		$settings[ 'class_available_' . $shipping_class->term_id ] = [
			/* translators: %s: shipping class name */
			'title'   => sprintf( __( '"%s" available', 'woomp' ), esc_html( $shipping_class->name ) ),
			'type'    => 'checkbox',
			'default' => $this->get_option( 'class_available_' . $shipping_class->term_id, 'yes' ),
		];
		$settings[ 'class_cost_' . $shipping_class->term_id ]      = [
			/* translators: %s: shipping class name */
			'title'             => sprintf( __( '"%s" shipping class cost', 'woocommerce' ), esc_html( $shipping_class->name ) ),
			'type'              => 'text',
			'placeholder'       => __( 'N/A', 'woocommerce' ),
			'description'       => $cost_desc,
			'default'           => $this->get_option( 'class_cost_' . $shipping_class->slug ),
			'desc_tip'          => true,
			'sanitize_callback' => [ $this, 'sanitize_cost' ],
		];
	}

	$settings['type'] = [
		'title'   => __( 'Calculation type', 'woocommerce' ),
		'type'    => 'select',
		'class'   => 'wc-enhanced-select',
		'default' => 'class',
		'options' => [
			'class' => __( 'Per class: Charge shipping for each shipping class individually', 'woocommerce' ),
			'order' => __( 'Per order: Charge shipping for the most expensive shipping class', 'woocommerce' ),
		],
	];
}

return $settings;
