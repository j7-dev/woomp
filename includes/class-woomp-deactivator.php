<?php

/**
 * Fired during plugin deactivation
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Woomp
 * @subpackage Woomp/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Woomp
 * @subpackage Woomp/includes
 * @author     More Power <a@a.a>
 */
class Woomp_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// 把預設的貨到付款改回來
		$gateway_setting = [
			'enabled'            => 'yes',
			'title'              => '貨到付款',
			'description'        => '收到貨時以現金付款。',
			'instructions'       => '收到貨時以現金付款。',
			'enable_for_methods' => [],
		];
		// update_option( 'woocommerce_cod_settings', $gateway_setting );
	}
}
