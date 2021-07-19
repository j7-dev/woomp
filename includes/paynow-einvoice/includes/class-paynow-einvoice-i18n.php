<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.paynow.com.tw/
 * @since      1.0.0
 *
 * @package    Paynow_Einvoice
 * @subpackage Paynow_Einvoice/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Paynow_Einvoice
 * @subpackage Paynow_Einvoice/includes
 * @author     PayNow <hello@paynow.com.tw>
 */
class Paynow_Einvoice_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'paynow-einvoice',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
