<?php

defined( 'ABSPATH' ) || exit;

define( 'PAYUNI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PAYUNI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PAYUNI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoload
 */
add_action(
	'plugins_loaded',
	function() {
		if ( wc_string_to_bool( get_option( 'wc_woomp_enabled_payuni_gateway' ) ) || wc_string_to_bool( get_option( 'wc_woomp_enabled_payuni_shipping' ) ) ) {
			\A7\autoload( WOOMP_PLUGIN_DIR . 'includes/payuni/src' );

			if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
				wp_die( 'WC_Payment_Gateway not found' );
			}
			\PAYUNI\APIs\Payment::init();
		}
	}
);

add_action(
	'wp_enqueue_scripts',
	function() {
		wp_enqueue_script( 'card-mask', 'https://cdnjs.cloudflare.com/ajax/libs/imask/3.4.0/imask.min.js', array(), '1.0.0', true );
		wp_enqueue_style( 'card', PAYUNI_PLUGIN_URL . 'assets/card.css', array(), '1.0.6' );
		wp_register_script( 'card', PAYUNI_PLUGIN_URL . 'assets/card.js', array( 'jquery' ), '1.3.4', true );
		wp_localize_script(
			'card',
			'card_params',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( 'payuni_card_change' ),
				'user_id'    => get_current_user_id(),
			)
		);
		wp_enqueue_script( 'card' );
	}
);
