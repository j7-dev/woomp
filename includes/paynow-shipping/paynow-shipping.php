<?php
/**
 * The PayNow shipping plugin file.
 *
 * @since   1.0.0
 * @package paynow
 *
 * @wordpress-plugin
 * Plugin Name:       PayNow Shipping
 * Description:       PayNow Shipping for WooCommerce
 * Plugin URI:        https://paynow.yangsheep.art
 * Version:           1.0.5
 * Author:            PayNow
 * Author URI:        https://www.paynow.com.tw/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       paynow-shipping
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'PAYNOW_SHIPPING_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PAYNOW_SHIPPING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PAYNOW_SHIPPING_BASENAME', plugin_basename( __FILE__ ) );
define( 'PAYNOW_SHIPPING_TEMPLATE_DIR', plugin_dir_path( __FILE__ ) . '/templates/' );

/**
 * Add PayNow shipping methods.
 *
 * @param array $methods Payment methods.
 * @return array
 */
function add_paynow_shipping_methods( $methods ) {
	$methods['paynow_shipping_c2c_711']    = 'PayNow_Shipping_C2C_711';
	$methods['paynow_shipping_c2c_family'] = 'PayNow_Shipping_C2C_Family';
	$methods['paynow_shipping_c2c_hilife'] = 'PayNow_Shipping_C2C_Hilife';
	$methods['paynow_shipping_hd_tcat']    = 'PayNow_Shipping_HD_TCat';
	return $methods;
}

/**
 * Plugin action links.
 *
 * @param array $links The action links array.
 * @return array
 */
function paynow_shipping_add_action_links( $links ) {
	$setting_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=paynow&section=shipping' ) . '">' . __( 'General Settings', 'paynow-shipping' ) . '</a>',
	);
	return array_merge( $links, $setting_links );
}

/**
 * PayNow shipping settings.
 *
 * @return PayNow_Shipping_Settings_Tab
 */
function paynow_shipping_add_settings() {
	include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/settings/class-paynow-shipping-settings-tab.php';
	return new PayNow_Shipping_Settings_Tab();
}

/**
 * Initialize PayNow shipping.
 *
 * @return void
 */
function run_paynow_shipping() {
	if ( ! class_exists( 'WC_Shipping_Method' ) ) {
		return;
	}

	include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/class-paynow-shipping.php';
	include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/admin/meta-boxes/class-paynow-shipping-order-meta-box.php';
	include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/admin/meta-boxes/class-paynow-shipping-order-admin.php';
	include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/utils/class-paynow-shipping-logistic-service.php';
	include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/utils/class-paynow-shipping-order-meta.php';
	include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/utils/class-paynow-shipping-status.php';
	include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/utils/paynow-shipping-functions.php';
	include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/shippings/abstract-paynow-shipping.php';
	include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/shippings/class-paynow-shipping-c2c-711.php';
	include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/shippings/class-paynow-shipping-c2c-family.php';
	include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/shippings/class-paynow-shipping-c2c-hilife.php';
	include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/shippings/class-paynow-shipping-hd-tcat.php';
	include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/shippings/api/class-paynow-shipping-request.php';
	include_once PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/shippings/api/class-paynow-shipping-response.php';

	load_plugin_textdomain( 'paynow-shipping', false, dirname( PAYNOW_SHIPPING_BASENAME ) . '/languages/' );

	add_filter( 'plugin_action_links_' . PAYNOW_SHIPPING_BASENAME, 'paynow_shipping_add_action_links' );
	add_filter( 'woocommerce_get_settings_pages', 'paynow_shipping_add_settings', 15 );
	add_filter( 'woocommerce_shipping_methods', 'add_paynow_shipping_methods' );

	PayNow_Shipping_Order_Admin::instance();
	PayNow_Shipping::init();
	PayNow_Shipping_Request::init();
	PayNow_Shipping_Response::init();

}
add_action( 'plugins_loaded', 'run_paynow_shipping' );
