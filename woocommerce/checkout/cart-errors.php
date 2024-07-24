<?php
defined( 'ABSPATH' ) || exit;

if ( 'onepage' === get_option( 'wc_woomp_setting_mode' ) || 'twopage' === get_option( 'wc_woomp_setting_mode' ) ) {
	$non_js_checkout = ! empty( $_POST['woocommerce_checkout_update_totals'] ); // WPCS: input var ok, CSRF ok.

	if ( wc_notice_count( 'error' ) === 0 && $non_js_checkout ) {
		wc_add_notice( __( 'The order totals have been updated. Please confirm your order by pressing the "Place order" button at the bottom of the page.', 'woocommerce' ) );
	}
	wc_get_template( 'checkout/form-checkout.php', [ 'checkout' => $checkout ] );
} else {
	wc_get_template( 'checkout/cart-errors.php', [ 'checkout' => $checkout ] );
	wc_clear_notices();
}
