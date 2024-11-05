<?php
/**
 * Form fields
 */

return [
	'title' => [
		'title'       => __( 'Title', 'paynow-shipping' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'paynow-shipping' ),
		'default'     => '',
		'desc_tip'    => true,
	],
	'description' => [
		'title'       => __( 'Description', 'paynow-shipping' ),
		'type'        => 'textarea',
		'description' => __( 'This controls the description which the user sees during checkout.', 'paynow-shipping' ),
		'desc_tip'    => true,
	],
	'tax_status' => [
		'title'   => __( 'Tax status', 'woocommerce' ),
		'type'    => 'select',
		'default' => 'none',
		'options' => [
			'taxable' => __( 'Taxable', 'woocommerce' ),
			'none'    => _x( 'None', 'Tax status', 'woocommerce' ),
		],
		'class'   => 'wc-enhanced-select',
	],
	'cost' => [
		'title'   => __( 'Shipping Cost', 'paynow-shipping' ),
		'type'    => 'number',
		'default' => 0,
		'min'     => 0,
		'step'    => 1,
	],
	'requires' => [
		'title'   => __( 'Free shipping requires', 'woocommerce' ),
		'type'    => 'select',
		'class'   => 'wc-enhanced-select',
		'default' => '',
		'options' => [
			''           => __( 'No requirement', 'woocommerce' ),
			'coupon'     => __( 'A valid free shipping coupon', 'woocommerce' ),
			'min_amount' => __( 'A minimum order amount', 'woocommerce' ),
			'either'     => __( 'A minimum order amount OR coupon', 'woocommerce' ),
			'both'       => __( 'A minimum order amount AND coupon', 'woocommerce' ),
		],
	],
	'min_amount' => [
		'title'       => __( 'Minimum order amount', 'ry-woocommerce-tools' ),
		'type'        => 'price',
		'default'     => 0,
		'placeholder' => wc_format_localized_price( 0 ),
		'description' => __( 'Users will need to spend this amount to get free shipping (if enabled above).', 'woocommerce' ),
		'desc_tip'    => true,
	],
	'ignore_discounts' => [
		'title'       => __( 'Coupons discounts', 'woocommerce' ),
		'label'       => __( 'Apply minimum order rule before coupon discount', 'woocommerce' ),
		'type'        => 'checkbox',
		'description' => __( 'If checked, free shipping would be available based on pre-discount order amount.', 'woocommerce' ),
		'default'     => 'no',
		'desc_tip'    => true,
	],
	'weight_plus_cost' => [
		// translators: %s WooCommerce weight unit
		'title'       => sprintf( __( 'Every weight (%s) to plus times of cost', 'ry-woocommerce-tools' ), __( get_option( 'woocommerce_weight_unit' ), 'woocommerce' ) ),
		'type'        => 'number',
		'default'     => 0,
		'placeholder' => 0,
		'description' => __( 'Calculate free shipping first. 0 to disable plus cost by weight.', 'ry-woocommerce-tools' ),
		'desc_tip'    => true,
	],
];
