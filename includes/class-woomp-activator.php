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
		if ( ! get_option( 'wc_woomp_setting_mode' ) ) {
			update_option( 'wc_woomp_setting_mode', 'default' );
		}
		if ( ! get_option( 'wc_woomp_setting_mode_twopage_message' ) ) {
			update_option( 'wc_woomp_setting_mode_twopage_message', '若需修改商品數量，請<a href="/cart">點此回到購物車</a>' );
		}
		if ( ! get_option( 'wc_woomp_setting_billing_country_pos' ) ) {
			update_option( 'wc_woomp_setting_billing_country_pos', 'yes' );
		}
		if ( ! get_option( 'wc_woomp_setting_tw_address' ) ) {
			update_option( 'wc_woomp_setting_tw_address', 'yes' );
		}
		if ( ! get_option( 'wc_woomp_setting_one_line_address' ) ) {
			update_option( 'wc_woomp_setting_one_line_address', 'yes' );
		}
		if ( ! get_option( 'wc_woomp_setting_show_phone' ) ) {
			update_option( 'wc_woomp_setting_show_phone', 'yes' );
		}
		if ( ! get_option( 'wc_woomp_setting_product_variations_ui' ) ) {
			update_option( 'wc_woomp_setting_product_variations_ui', 'yes' );
		}
		if ( ! get_option( 'wc_woomp_setting_product_variations_frontend_ui' ) ) {
			update_option( 'wc_woomp_setting_product_variations_frontend_ui', 'yes' );
		}
		if ( ! get_option( 'wc_woomp_setting_tw_field_valitdate' ) ) {
			update_option( 'wc_woomp_setting_tw_field_valitdate', 'yes' );
		}
		if ( ! get_option( 'woocommerce_woomp_cod_gateway_settings' ) ) {
			$gateway_setting = array(
				'enabled'            => 'no',
				'title'              => '貨到付款',
				'description'        => '收到貨時以現金付款。',
				'instructions'       => '收到貨時以現金付款。',
				'enable_for_methods' => array( 'ry_ecpay_shipping_home_ecan:7', 'ry_ecpay_shipping_home_tcat:6' ),
			);
			update_option( 'woocommerce_woomp_cod_gateway_settings', $gateway_setting );
		}

		// 啟動外掛自動調整物流項目到預設的顧客帳單地址
		if ( 'billing' !== get_option( 'woocommerce_ship_to_destination' ) ) {
			update_option( 'woocommerce_ship_to_destination', 'billing' );
		}

		// 將預設貨到付款文字改為超商取貨付款
		if ( ! get_option( 'woocommerce_cod_settings' ) ) {
			$gateway_setting = array(
				'enabled'            => 'no',
				'title'              => '超商取貨付款',
				'description'        => '可將商品送到指定的超商門市，取貨時再進行付款。',
				'instructions'       => '可將商品送到指定的超商門市，取貨時再進行付款。',
				'enable_for_methods' => array( 'ry_ecpay_shipping_cvs_711', 'ry_ecpay_shipping_cvs_711:3', 'ry_ecpay_shipping_cvs_hilife', 'ry_ecpay_shipping_cvs_hilife:4', 'ry_ecpay_shipping_cvs_family', 'ry_ecpay_shipping_cvs_family:5', 'ry_newebpay_shipping_cvs:15' ),
			);
			update_option( 'woocommerce_cod_settings', $gateway_setting );
		}
	}
}
