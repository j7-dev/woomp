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
	public function get_transaction_args( $order, $card_data = null ) {

		$args = apply_filters(
			'payuni_transaction_args_' . $this->gateway->id,
			array(
				'MerID'      => $this->gateway->get_mer_id(),
				'MerTradeNo' => $order->get_order_number(),
				'TradeAmt'   => $order->get_total(),
				'Timestamp'  => time(),
				'UsrMail'    => $order->get_billing_email(),
				'ProdDesc'   => $this->get_product_name( $order ),
			),
			$order
		);

		if ( $card_data ) {
			if ( $order->get_meta( '_' . $this->gateway->id . '-card_hash' ) ) {
				// 有記憶卡號的情況.
				$data = array(
					'CreditToken' => $order->get_billing_email(),
					'CreditHash'  => $order->get_meta( '_' . $this->gateway->id . '-card_hash' ),
				);
			} else {
				$data = array(
					'CardNo'      => $card_data['number'],
					'CardExpired' => $card_data['expiry'],
					'CardCVC'     => $card_data['cvc'],
					'CreditToken' => $order->get_billing_email(),
				);
			}

			$args = array_merge(
				$args,
				$data
			);
		}

		\PAYUNI\APIs\Payment::log( $args );

		$parameter['MerID']       = $this->gateway->get_mer_id();
		$parameter['Version']     = '1.0';
		$parameter['EncryptInfo'] = \Payuni\APIs\Payment::encrypt( $args );
		$parameter['HashInfo']    = \Payuni\APIs\Payment::hash_info( $parameter['EncryptInfo'] );

		return $parameter;
	}

	/**
	 * Get product name
	 *
	 * @param WC_Order $order The order object.
	 */
	public function get_product_name( $order ) {
		$items = $order->get_items();
		if ( ! empty( $items ) ) {
			foreach ( $items as $item ) {
				return $item->get_name();
			}
		}
		return '商品名稱';
	}

	/**
	 * Generate the form and redirect to PayNow
	 *
	 * @param WC_Order $order The order object.
	 * @return void
	 */
	public function build_request( $order, $card_data = null ) {
		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'body'    => $this->get_transaction_args( $order, $card_data ),
		);

		$response = wp_remote_request( $this->gateway->get_api_url() . $this->gateway->get_api_endpoint_url(), $options );

		$resp = json_decode( wp_remote_retrieve_body( $response ) );
		$data = \Payuni\APIs\Payment::decrypt( $resp->EncryptInfo );

		if ( $data['URL'] ) {
			return $data['URL'];
		}

		//if ( $card_data ) {
		//	Response::card_response( $resp );
		//	return;
		//}

		switch ( $method ) {
			case 'payuni-atm':
				Response::atm_response( $resp );
				break;
			case 'payuni-cvs':
				Response::cvs_response( $resp );
				break;
			default:
				// code...
				break;
		}
	}
}
