<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define Form Fields to be used in LINEPay Admin.
 *
 * @class WC_Gateway_LINEPay_Admin
 * @version 1.0.0
 * @author LINEPay
 */
class WC_Gateway_LINEPay_Settings {

	/**
	 * @var WC_Gateway_LINEPay
	 */
	private $linepay_gateway;

	/**
	 * Setup class.
	 *
	 * @param WC_Gateway_LINEPay $linepay_gateway LINE Pay gateway instance.
	 * @since 1.0.0
	 */
	public function __construct( $linepay_gateway ) {
		$this->linepay_gateway = $linepay_gateway;
	}

	/**
	 * Returns Form Fields information as an array.
	 *
	 * @return array
	 */
	public function get_form_fields() {

		return [
			'enabled'     => [
				'title'   => __( 'Enable/Disable', 'woocommerce-gateway-linepay' ),
				'type'    => 'checkbox',
				'label'   => __( 'Starting up LINE Pay plugin...', 'woocommerce-gateway-linepay' ),
				'default' => 'no',
			],
			'title'       => [
				'title'       => __( 'Title', 'woocommerce-gateway-linepay' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-gateway-linepay' ),
				'default'     => __( 'Line Pay', 'woocommerce-gateway-linepay' ),
				'desc_tip'    => true,
			],
			'description' => [
				'title'       => __( 'Description', 'woocommerce-gateway-linepay' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-gateway-linepay' ),
				'desc_tip'    => true,
				'css'         => 'width:400px;',
			],
		];
	}
}
