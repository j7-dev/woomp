<?php
return [
	[
		'title' => __( 'PAYUNi Shipping Setting', 'woomp' ),
		'id'    => 'base_options',
		'type'  => 'title',
	],

	[
		'title'   => __( 'Debug log', 'woocommerce' ),
		'id'      => 'payuni_shipping_log',
		'type'    => 'checkbox',
		'default' => 'no',
		'desc'    => __( 'Enable logging', 'woocommerce' ) . '<br>'
			. sprintf(
				/* translators: %s: Path of log file */
				__( 'Log PAYUNi shipping events/message, inside %s', 'woomp' ),
				'<code>' . WC_Log_Handler_File::get_log_file_path( 'payuni_shipping' ) . '</code>'
			),
	],

	[
		'id'   => 'base_options',
		'type' => 'sectionend',
	],
	[
		'title' => __( 'Shipping note options', 'woomp' ),
		'id'    => 'note_options',
		'type'  => 'title',
	],
	[
		'title'    => __( 'Order no prefix', 'woomp' ),
		'id'       => 'payuni_shipping_order_prefix',
		'type'     => 'text',
		'desc'     => __( 'The prefix string of order no. Only letters and numbers allowed allowed.', 'woomp' ),
		'desc_tip' => true,
	],
	[
		'title'    => __( 'shipping item name', 'woomp-pro' ),
		'id'       => 'shipping_item_name',
		'type'     => 'text',
		'default'  => '',
		'desc'     => __( 'If empty use the first product name.', 'woomp-pro' ),
		'desc_tip' => true,
	],
	// array(
	// 'title'   => __( 'Cvs shipping type', 'woomp' ),
	// 'id'      => 'payuni_shipping_cvs_type',
	// 'type'    => 'select',
	// 'default' => 'C2C',
	// 'options' => array(
	// 'B2C' => _x( 'B2C', 'Cvs type', 'woomp' ),
	// ),
	// ),
	[
		'title'    => __( 'Sender name', 'woomp' ),
		'id'       => 'payuni_shipping_sender_name',
		'type'     => 'text',
		'desc'     => __( 'Name length between 1 to 10 letter', 'woomp' ),
		'desc_tip' => true,
	],
	[
		'title'             => __( 'Sender phone', 'woomp' ),
		'id'                => 'payuni_shipping_sender_phone',
		'type'              => 'text',
		'desc'              => __( 'Phone format (0x)xxxxxxx#xx', 'woomp' ),
		'desc_tip'          => true,
		'placeholder'       => '(0x)xxxxxxx#xx',
		'custom_attributes' => [
			'pattern' => '\(0\d{1,2}\)\d{6,8}(#\d+)?',
		],
	],
	[
		'title'             => __( 'Sender cellphone', 'woomp' ),
		'id'                => 'payuni_shipping_sender_cellphone',
		'type'              => 'text',
		'desc'              => __( 'Cellphone format 09xxxxxxxx', 'woomp' ),
		'desc_tip'          => true,
		'placeholder'       => '09xxxxxxxx',
		'custom_attributes' => [
			'pattern' => '09\d{8}',
		],
	],
	[
		'title' => __( 'Sender zipcode', 'woomp' ),
		'id'    => 'payuni_shipping_sender_zipcode',
		'type'  => 'text',
	],
	[
		'title' => __( 'Sender address', 'woomp' ),
		'id'    => 'payuni_shipping_sender_address',
		'type'  => 'text',
	],
	[
		'id'   => 'note_options',
		'type' => 'sectionend',
	],
	[
		'title' => __( 'API credentials', 'woomp' ),
		'id'    => 'api_options',
		'type'  => 'title',
	],
	[
		'title'   => __( 'payuni shipping sandbox', 'woomp' ),
		'id'      => 'payuni_shipping_testmode',
		'type'    => 'checkbox',
		'default' => 'yes',
		'desc'    => __( 'Enable payuni shipping sandbox', 'woomp' ),
	],
	[
		'title'   => __( 'MerchantID', 'woomp' ),
		'id'      => 'payuni_shipping_MerchantID',
		'type'    => 'text',
		'default' => '',
	],
	[
		'title'   => __( 'HashKey', 'woomp' ),
		'id'      => 'payuni_shipping_HashKey',
		'type'    => 'text',
		'default' => '',
	],
	[
		'title'   => __( 'HashIV', 'woomp' ),
		'id'      => 'payuni_shipping_HashIV',
		'type'    => 'text',
		'default' => '',
	],
	[
		'id'   => 'api_options',
		'type' => 'sectionend',
	],
];
