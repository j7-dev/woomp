<?php
/**
 * Abstract PayNow shipping method
 *
 * PayNow_Shipping_Method abstract class file.
 *
 * @package paynow
 */

defined( 'ABSPATH' ) || exit;

/**
 * The PayNow shipping base payment method.
 */
abstract class PayNow_Abstract_Shipping_Method extends WC_Shipping_Method {

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	protected $plugin_name = 'paynow-shipping';

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * Javascript data for api call.
	 *
	 * @var array
	 */
	public static $js_data;

	/**
	 * Logistic service.
	 *
	 * @see PayNow_Shipping_Logistic_Service
	 * @var string
	 */
	public $logistic_service;

	/**
	 * Minimum order amount for free shipping.
	 *
	 * @var int
	 */
	public $free_shipping_requires;

	/**
	 * Minimum order amount for free shipping.
	 *
	 * @var int
	 */
	public $free_shipping_min_amount;

	/**
	 * Constructor function
	 */
	public function __construct() {
		$this->supports = array(
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		);

	}

	/**
	 * Get shipping cost and check if met free shipping
	 *
	 * @return int
	 */
	protected function get_cost() {

		if ( 'min_amount' === $this->free_shipping_requires ) {
			$total = WC()->cart->get_displayed_subtotal();

			if ( WC()->cart->display_prices_including_tax() ) {
				$total = $total - WC()->cart->get_discount_tax();
			}

			$total = round( $total, wc_get_price_decimals() );

			if ( $total >= $this->free_shipping_min_amount ) {
				return 0;
			}
		}

		return $this->cost;

	}

	/**
	 * Get plugin name.
	 *
	 * @return string
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Get plugin version.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}
}
