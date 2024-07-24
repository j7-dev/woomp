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

		if ( ! wp_verify_nonce( $_POST['nonce'], 'pay_manual' ) ) {
			echo __( '發生錯誤，不合法的請求來源！', 'woomp' );
			exit;
		}

		$order_id = intval( sanitize_text_field( $_POST['orderId'] ) );

		if ( ! $order_id ) {
			return false;
		}

		$order  = wc_get_order( $order_id );
		$amount = $order->get_total();
		$this->request->build_subscription_request( $amount, $order );

		echo 'success';

		wp_die();
	}
}

Ajax::init();
