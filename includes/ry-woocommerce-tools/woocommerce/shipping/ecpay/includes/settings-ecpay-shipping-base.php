<?php
$settings = [
	'title'            => [
		'title'       => __( 'Title', 'woocommerce' ),
		'type'        => 'text',
		'default'     => $this->method_title,
		'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
		'desc_tip'    => true,
	],
	'description'      => [
		'title'       => __( 'Description', 'woocommerce' ),
		'type'        => 'textarea',
		'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
		'desc_tip'    => true,
	],
	'tax_status'       => [
		'title'   => __( 'Tax status', 'woocommerce' ),
		'type'    => 'select',
		'default' => 'none',
		'options' => [
			'taxable' => __( 'Taxable', 'woocommerce' ),
			'none'    => _x( 'None', 'Tax status', 'woocommerce' ),
		],
		'class'   => 'wc-enhanced-select',
	],
	'cost'             => [
		'title'   => __( 'Shipping cost', 'ry-woocommerce-tools' ),
		'type'    => 'number',
		'default' => 0,
		'min'     => 0,
		'step'    => 1,
	],
	'requires'         => [
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
	'min_amount'       => [
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

$shipping_classes = WC()->shipping->get_shipping_classes();
$cost_desc        = __( 'Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.', 'woocommerce' ) . '<br/><br/>' . __( 'Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent="10" min_fee="20" max_fee=""]</code> for percentage based fees. Also there is only one shipping class in the cart allowed. You can\'t add the products of different shipping classes.', 'woomp' );

if ( ! empty( $shipping_classes ) ) {
	$settings['class_available'] = [
		'title'       => __( 'Shipping available', 'ry-woocommerce-tools' ),
		'type'        => 'title',
		'default'     => '',
		/* translators: %s: shipping class setting url */
		'description' => sprintf( __( 'These shipping available based on the <a href="%s">product shipping class</a>.', 'ry-woocommerce-tools' ), admin_url( 'admin.php?page=wc-settings&tab=shipping&section=classes' ) ),
	];
	foreach ( $shipping_classes as $shipping_class ) {
		if ( ! isset( $shipping_class->term_id ) ) {
			continue;
		}
		$settings[ 'class_available_' . $shipping_class->term_id ] = [
			/* translators: %s: shipping class name */
			'title'   => sprintf( __( '"%s" available', 'ry-woocommerce-tools' ), esc_html( $shipping_class->name ) ),
			'type'    => 'checkbox',
			'default' => $this->get_option( 'class_available_' . $shipping_class->term_id, 'yes' ),
		];
		$settings[ 'class_limit_' . $shipping_class->term_id ]     = [
			/* translators: %s: shipping class name */
			'title'   => sprintf( __( '"%s" shipping class limits', 'woomp' ), esc_html( $shipping_class->name ) ),
			'type'    => 'select',
			'default' => $this->get_option( 'class_limit_' . $shipping_class->term_id, 'yes' ),
			'options' => [
				'unlimit' => __( 'No, by the calculation type', 'woomp' ),
				'limit'   => __( 'Yes, adding to cart is not allowed if there are different shipping classes in it', 'woomp' ),
			],
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
