<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Woomp
 * @subpackage Woomp/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Woomp
 * @subpackage Woomp/includes
 * @author     More Power <a@a.a>
 */
class Woomp_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'woomp',
			false,
			dirname( plugin_basename( __FILE__ ), 2 ) . '/languages/'
		);

		load_plugin_textdomain(
			'ry-woocommerce-tools',
			false,
			dirname( plugin_basename( __FILE__ ), 2 ) . '/languages/'
		);

		load_plugin_textdomain(
			'ry-woocommerce-tools-pro',
			false,
			dirname( plugin_basename( __FILE__ ), 2 ) . '/languages/'
		);

		load_plugin_textdomain(
			'ry-woocommerce-ecpay-invoice',
			false,
			dirname( plugin_basename( __FILE__ ), 2 ) . '/languages/'
		);

		load_plugin_textdomain(
			'paynow-payment',
			false,
			dirname( plugin_basename( __FILE__ ), 2 ) . '/languages/'
		);

		load_plugin_textdomain(
			'paynow-einvoice',
			false,
			dirname( plugin_basename( __FILE__ ), 2 ) . '/languages/'
		);
		load_plugin_textdomain(
			'paynow-shipping',
			false,
			dirname( plugin_basename( __FILE__ ), 2 ) . '/languages/'
		);
	}
}
