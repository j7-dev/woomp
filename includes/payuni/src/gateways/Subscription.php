<?php

namespace PAYUNI\Gateways;

defined( 'ABSPATH' ) || exit;

class Subscription {
	/**
	 * Initialize class and add hooks
	 *
	 * @return void
	 */
	public static function init() {
		$class = new self();
		add_action( 'woocommerce_scheduled_subscription_payment_payuni-credit-subscription', array( $class, 'pay' ), 10, 2 );
		//add_action( 'woocommerce_subscription_cancelled_payuni-credit-subscription', array( $class, 'pay' ) );
		//add_action( 'woocommerce_subscription_failing_payment_method_updated_payuni-credit-subscription', array( $class, 'pay' ) );

	}
	public function pay( $amount, $order ) {
		$log = new \WC_Logger();
//		$log->log( 'info', wc_print_r( $order, true ), array( 'source' => 'ods-log' ) );
		$log->log( 'info', wc_print_r( 'subscription class '.$amount, true ), array( 'source' => 'ods-log' ) );
//		$order_suffix = ( $order->get_meta( '_payuni_order_suffix' ) ) ? '-' . $order->get_meta( '_payuni_order_suffix' ) : '';
//		$options = array(
//			'method'  => 'POST',
//			'timeout' => 60,
//			'body'    => array(
//				'MerID'      => $this->gateway->get_mer_id(),
//				'MerTradeNo' => $order->get_order_number() . $order_suffix,
//				'TradeAmt'   => $order->get_total(),
//				'Timestamp'  => time(),
//				'UsrMail'    => $order->get_billing_email(),
//				'ProdDesc'   => $this->get_product_name( $order ),
//				'CreditToken' => $order->get_billing_email(),
//				'CreditHash'  => $order->get_meta( '_payuni-credit-subscription-card_hash' ),
//			),
//		);
//
//		$response = wp_remote_request( $this->gateway->get_api_url() . $this->gateway->get_api_endpoint_url(), $options );
//		$resp     = json_decode( wp_remote_retrieve_body( $response ) );
//
//		$data = \Payuni\APIs\Payment::decrypt( $resp->EncryptInfo );
	}
}

//Subscription::init();
