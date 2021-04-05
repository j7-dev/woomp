<?php

/**
 * Fired during plugin activation
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Woomp
 * @subpackage Woomp/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Woomp
 * @subpackage Woomp/includes
 * @author     More Power <a@a.a>
 */
class Woomp_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		if( !get_option( 'wc_woomp_setting_replace' ) )
			update_option( 'wc_woomp_setting_replace', 'yes' );
		if( !get_option( 'wc_woomp_setting_billing_country_pos' ) )
			update_option( 'wc_woomp_setting_billing_country_pos', 'yes' );
		if( !get_option( 'wc_woomp_setting_tw_address' ) )
			update_option( 'wc_woomp_setting_tw_address', 'yes' );
		if( !get_option( 'wc_woomp_setting_one_line_address' ) )
			update_option( 'wc_woomp_setting_one_line_address', 'yes' );
		if( !get_option( 'wc_woomp_setting_cvs_payment' ) )
			update_option( 'wc_woomp_setting_cvs_payment', 'yes' );
		if( !get_option( 'wc_woomp_setting_product_variations_ui' ) )
			update_option( 'wc_woomp_setting_product_variations_ui', 'yes' );
		if( !get_option( 'woocommerce_woomp_cvs_gateway_settings' ) ){
			$gateway_setting = array(
				'enabled' => 'yes',
				'title'   => '超商取貨付款',
				'description'   => '可將商品送到指定的超商門市，取貨時再進行付款。',
				'instructions'   => '可將商品送到指定的超商門市，取貨時再進行付款。',
				'enable_for_methods'   => array( 'ry_ecpay_shipping_cvs_711', 'ry_ecpay_shipping_cvs_711:3', 'ry_ecpay_shipping_cvs_hilife', 'ry_ecpay_shipping_cvs_hilife:4', 'ry_ecpay_shipping_cvs_family', 'ry_ecpay_shipping_cvs_family:5', 'ry_newebpay_shipping_cvs:15' )
			); 
			update_option( 'woocommerce_woomp_cvs_gateway_settings', $gateway_setting );
		}
	}

}
