<?php
/**
 * Settings for PayNow c2c 7-11 frozen shipping method.
 *
 * @package woomp
 */

defined( 'ABSPATH' ) || exit;

/**
 * Initialize the PayNow shipping method.
 *
 * @return void
 */
function run_woomp_paynow_shipping() {
	/**
	 * Autoload
	 */
	\Woomp\A7\autoload( WOOMP_PLUGIN_DIR . 'includes/woomp-paynow-shipping/src' );

	add_filter( 'woocommerce_shipping_methods', 'woomp_add_paynow_shipping_methods' );

	WOOMP_PayNow_Shipping::init();
}
add_action( 'plugins_loaded', 'run_woomp_paynow_shipping', '999' );

/**
 * Add PayNow shipping methods.
 *
 * @param array $methods Shipping methods.
 * @return array
 */
function woomp_add_paynow_shipping_methods( $methods ) {
	$methods['woomp_paynow_shipping_c2c_711_frozen']       = 'WOOMP_PayNow_Shipping_C2C_711_Frozen';
	$methods['woomp_paynow_shipping_c2c_family_frozen']    = 'WOOMP_PayNow_Shipping_C2C_Family_Frozen';
	$methods['woomp_paynow_shipping_hd_tcat_frozen']       = 'WOOMP_PayNow_Shipping_HD_TCat_Frozen';
	$methods['woomp_paynow_shipping_hd_tcat_refrigerated'] = 'WOOMP_PayNow_Shipping_HD_TCat_Refrigerated';
	return $methods;
}
