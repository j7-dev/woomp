<?php

namespace PAYUNI\Posts\ShopOrder;

use PAYUNI\Gateways\CreditSubscription;
use PAYUNI\Gateways\Request;

defined( 'ABSPATH' ) || exit;

class Ajax {

	private $request;

	public static function init() {
		$class = new self();
		add_action( 'wp_ajax_payuni_subscription_pay_manual', [ $class, 'build_request' ] );
	}

	public function __construct() {
		$this->request = new Request( new CreditSubscription() );
	}

	/**
	 * 手動請款
	 */
	public function build_request() {
		try {
			if ( ! wp_verify_nonce( $_POST['nonce'], 'pay_manual' ) ) { // phpcs:ignore
				\wp_send_json_error( __( '發生錯誤，不合法的請求來源！', 'woomp' ) );
				return;
			}

			$order_id = (int) $_POST['orderId'] ?? 0; // phpcs:ignore

			if ( ! $order_id ) {
				\wp_send_json_error( __( '訂單編號錯誤！', 'woomp' ) );
				return;
			}

			$order  = \wc_get_order( $order_id );
			$amount = $order->get_total();
			$this->request->build_subscription_request( (float) $amount, $order );

			\wp_send_json_success( __( '扣款成功！即將刷新頁面', 'woomp' ) );

			\wp_die();
		} catch (\Throwable $th) {
			ob_start();
			var_dump( $th );
			\J7\WpUtils\Classes\ErrorLog::info( 'error: ' . ob_get_clean() );
		}
	}
}

Ajax::init();
