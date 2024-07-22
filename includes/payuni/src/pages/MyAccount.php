<?php
/**
 * MyAccount class
 */

namespace PAYUNI\Pages;

defined( 'ABSPATH' ) || exit;

/**
 * MyAccount class
 */
class MyAccount {
	/**
	 * Initialize class and add hooks
	 *
	 * @return void
	 */
	public static function init() {
		$class = new self();
		\add_filter(
			'woocommerce_my_account_my_orders_actions',
			array(
				$class,
				'change_customer_order_action',
			),
			10,
			2
		);
		\add_action( 'wp_enqueue_scripts', array( $class, 'enqueue_my_account_script' ) );
		\add_action( 'woocommerce_payment_token_set_default', array( $class, 'update_credit_hash' ), 30, 2 );
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

	/**
	 * 當用戶設定信用卡為預設時，更新訂閱的上層訂單中的信用卡資訊
	 *
	 * @see https://github.com/j7-dev/woomp/issues/37
	 *
	 * @param integer              $token_id token_id.
	 * @param \WC_PAYMENT_TOKEN_CC $token 信用卡 token 資訊.
	 *
	 * @return void
	 */
	public function update_credit_hash( int $token_id, \WC_PAYMENT_TOKEN_CC $token ): void {
		if ( ! class_exists( 'WC_Subscriptions' ) ) {
			return;
		}

		$user_id = $token->get_user_id(); // <--正確的用戶
		if ( ! $user_id ) {
			return;
		}
		// find all subscriptions for this user.
		$subscriptions = \wcs_get_users_subscriptions( $user_id );
		// get subscription post parent id.
		$parent_order_ids = array();
		foreach ( $subscriptions as $subscription ) {
			$subscription_id    = $subscription->get_id();
			$parent_id          = wp_get_post_parent_id( $subscription_id );
			$parent_order_ids[] = $parent_id;
		}

		foreach ( $parent_order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				continue;
			}
			$order->update_meta_data( '_payuni_card_hash', $token->get_token() );
			$order->update_meta_data( '_payuni_card_number', $token->get_last4() );
			$order->update_meta_data( '_payuni_resp_card_bank', '不確定' );
			$order->update_meta_data( '_payuni_resp_message', '修改預設付款方式' );
			$order->save();
		}
	}
}

MyAccount::init();
