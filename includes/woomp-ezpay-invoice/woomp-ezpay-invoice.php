<?php

defined( 'ABSPATH' ) || exit;

define( 'EZPAYINVOICE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EZPAYINVOICE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EZPAYINVOICE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoload
 */
if ( wc_string_to_bool( get_option( 'wc_woomp_enabled_ezpay_invoice' ) ) ) {
	\Woomp\A7\autoload( WOOMP_PLUGIN_DIR . 'includes/woomp-ezpay-invoice/src' );
}
