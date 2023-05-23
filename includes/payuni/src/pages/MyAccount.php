<?php

namespace PAYUNI\Pages;

defined( 'ABSPATH' ) || exit;

class MyAccount {
	/**
	 * Initialize class and add hooks
	 *
	 * @return void
	 */
	public static function init() {
		$class = new self();
		add_filter( 'woocommerce_my_account_my_orders_actions', array( $class, 'change_customer_order_action' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $class, 'enqueue_my_account_script' ) );
	}
	public function change_customer_order_action( $actions, $order ) {
		$order_status   = $order->get_status();
		$payment_method = $order->get_payment_method();

		switch ( $order_status ) {
			case 'failed':
				if ( 'linepay' !== $payment_method ) {
					break;
				}

				unset( $actions['pay'] );
				unset( $actions['cancel'] );

				break;
		}

		// $refund_expired = strtotime($order->get_date_created()->date('Y-m-d H:i:s') . ' -8 hour') + (60 * 86400);

		if ( strpos( $payment_method, 'payuni-credit' ) !== false && $order->get_total() - $order->get_total_refunded() > 0 ) {
			if ( in_array( 'wc-' . $order_status, get_option( 'payuni_customer_refund' ) ) ) {
				$actions['payuni-refund'] = array(
					'url'  => esc_url_raw(
						add_query_arg(
							array(
								'order_id' => $order->get_id(),
								'amount'   => $order->get_total() - $order->get_total_refunded(),
							),
							''
						)
					),
					'name' => '退款',
				);
			}
		}

		return $actions;
	}

	public function enqueue_my_account_script() {
		if ( is_account_page() ) {
			wp_register_script( 'payuni_my_account_script', WOOMP_PLUGIN_URL . 'includes/payuni/assets/my-account.js', array( 'jquery' ), '1.1.8', true );
			wp_localize_script(
				'payuni_my_account_script',
				'payuni_my_account_script_params',
				array(
					'ajax_url'   => admin_url( 'admin-ajax.php' ),
					'ajax_nonce' => wp_create_nonce( 'payuni_refund' ),
					'user_id'    => get_current_user_id(),
				)
			);
			wp_enqueue_script( 'payuni_my_account_script' );
		}
	}

}

MyAccount::init();
