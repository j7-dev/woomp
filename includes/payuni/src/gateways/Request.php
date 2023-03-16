<?php
/**
 * Payuni_Payment_Request class file
 *
 * @package payuni
 */

 namespace PAYUNI\Gateways;

defined( 'ABSPATH' ) || exit;

/**
 * Generates payment form and redirect to payuni
 */
class Request {

	/**
	 * The gateway instance
	 *
	 * @var WC_Payment_Gateway
	 */
	protected $gateway;

	/**
	 * Constructor
	 *
	 * @param  WC_Payment_Gateway $gateway The payment gateway instance.
	 */
	public function __construct( $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * Build transaction args.
	 *
	 * @param WC_Order $order The order object.
	 * @return array
	 */
	public function get_transaction_args( $order ) {

		$args = apply_filters(
			'payuni_transaction_args_' . $this->gateway->id,
			array(
				'MerID'      => $this->gateway->get_mer_id(),
				'MerTradeNo' => $order->get_order_number(),
				'TradeAmt'   => 1000,
				'Timestamp'  => time(),
				'UsrMail'    => 'm615926@gmail.com',
				'ProdDesc'   => '商品名稱',
				//'API3D'      => 1,
				'CardNo'      => $order->get_meta('_payuni_card_number'),
				'CardExpired' => $order->get_meta('_payuni_card_expiry'),
				'CardCVC'     => $order->get_meta('_payuni_card_cvc'),
			),
			$order
		);

		$parameter['MerID']       = $this->gateway->get_mer_id();
		$parameter['Version']     = '1.0';
		$parameter['EncryptInfo'] = $this->gateway->encrypt( $args );
		$parameter['HashInfo']    = $this->gateway->hash_info( $parameter['EncryptInfo'] );

		\PAYUNI\APIs\Payment::log( $args );

		return $parameter;
	}

	/**
	 * Generate the form and redirect to PayNow
	 *
	 * @param WC_Order $order The order object.
	 * @return void
	 */
	public function build_request( $order ) {
		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'body'    => $this->get_transaction_args( $order ),
		);

		return;

		$response = wp_remote_request( $this->gateway->get_api_url() . $this->gateway->get_api_endpoint_url(), $options );

		$resp = wp_remote_retrieve_body( $response );

		if ( ! is_wp_error( $response ) ) {

			\PAYUNI\APIs\Payment::log( $this->gateway->get_response( $response ) );

		} else {
			\PAYUNI\APIs\Payment::log( $response );
			wc_add_notice( '連線發生錯誤，請聯繫客服', 'error' );
			return;
		}
	}
}
