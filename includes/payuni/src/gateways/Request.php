<?php
/**
 * Payuni_Payment_Request class file
 *
 * @package payuni
 */

namespace PAYUNI\Gateways;

use PAYUNI\APIs\Payment;
use WC_Order;

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
	 * @param WC_Payment_Gateway $gateway The payment gateway instance.
	 */
	public function __construct( $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * Build transaction args.
	 *
	 * @param WC_Order   $order     The order object.
	 * @param array|null $card_data The card data.
	 *
	 * @return array
	 */
	public function get_transaction_args( WC_Order $order, array|null $card_data ): array {
		$order_suffix = ( $order->get_meta( '_payuni_order_suffix' ) ) ? '-' . $order->get_meta( '_payuni_order_suffix' ) : '';

		$args = apply_filters(
			'payuni_transaction_args_' . $this->gateway->id,
			array(
				'MerID'      => $this->gateway->get_mer_id(),
				'MerTradeNo' => $order->get_order_number() . $order_suffix,
				'TradeAmt'   => $order->get_total(),
				'Timestamp'  => time(),
				'UsrMail'    => $order->get_billing_email(),
				'ProdDesc'   => $this->get_product_name( $order ),
			),
			$order
		);

		if ( $card_data ) {
			$data = array(
				'CreditToken' => $order->get_billing_email(),
			);

			$order->update_meta_data( '_payuni_token_id', $card_data['token_id'] );
			$order->update_meta_data( '_payuni_token_maybe_save', $card_data['new'] );
			$order->save();

			if ( ! empty( $card_data['token_id'] ) && 'new' !== $card_data['token_id'] ) {
				$token              = \WC_Payment_Tokens::get( $card_data['token_id'] );
				$data['CreditHash'] = $token->get_token();
			} else {
				$data = array(
					'CardNo'      => $card_data['number'],
					'CardExpired' => $card_data['expiry'],
					'CardCVC'     => $card_data['cvc'],
					'CreditToken' => $order->get_billing_email(),
				);
			}

			if ( $card_data['period'] ) {
				$data['CardInst'] = $card_data['period'];
			}

			$args = array_merge(
				$args,
				$data
			);
		}

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
	 *
	 * @return array
	 */
	public function build_request( WC_Order $order, $card_data = null ): array {
		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'body'    => $this->get_transaction_args( $order, $card_data ),
		);

		$response = wp_remote_request( $this->gateway->get_api_url() . $this->gateway->get_api_endpoint_url(), $options );
		$resp     = json_decode( wp_remote_retrieve_body( $response ) );

		//@codingStandardsIgnoreStart
		$data = \Payuni\APIs\Payment::decrypt( $resp->EncryptInfo );
		//@codingStandardsIgnoreEnd

		unset( $data['Card6No'] ); // remove card number from log.

		Payment::log( $data, 'request' );

		// 結帳頁顯示錯誤訊息.
		if ( 'SUCCESS' !== $data['Status'] ) {
			wc_add_notice( $data['Message'], 'error' );
		}

		if ( key_exists( 'URL', $data ) ) {
			return array(
				'result'   => 'success',
				'redirect' => $data['URL'],
			);
		} else {
			$this->set_response( $order->get_payment_method(), $resp );

			return array(
				'result'   => 'success',
				'redirect' => $this->gateway->get_return_url( $order ),
			);
		}

	}

	/**
	 * The request for subscription.
	 *
	 * @param int      $amount The amount.
	 * @param WC_Order $order  The subscription order object.
	 */
	public function build_subscription_request( int $amount, WC_Order $order ): void {
		$order_suffix = ( $order->get_meta( '_payuni_order_suffix' ) ) ? '-' . $order->get_meta( '_payuni_order_suffix' ) : '';

		$args = array(
			'MerID'       => $this->gateway->get_mer_id(),
			'MerTradeNo'  => $order->get_order_number() . $order_suffix,
			'TradeAmt'    => $amount,
			'Timestamp'   => time(),
			'UsrMail'     => $order->get_billing_email(),
			'ProdDesc'    => $this->get_product_name( $order ),
			'CreditToken' => $order->get_billing_email(),
			'CreditHash'  => $this->get_card_hash( $order ),
		);

		$parameter = array(
			'MerID'       => $this->gateway->get_mer_id(),
			'Version'     => '1.0',
			'EncryptInfo' => \Payuni\APIs\Payment::encrypt( $args ),
			'HashInfo'    => \Payuni\APIs\Payment::hash_info( \Payuni\APIs\Payment::encrypt( $args ) ),
		);

		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'body'    => $parameter,
		);

		$response = wp_remote_request( $this->gateway->get_api_url() . $this->gateway->get_api_endpoint_url(), $options );
		$resp     = json_decode( wp_remote_retrieve_body( $response ) );

		$this->set_response( $order->get_payment_method(), $resp );
	}

	/**
	 * The request for get card hash without order.
	 *
	 * @param array $card_data The card data.
	 *
	 * @return bool
	 */
	public function build_hash_request( array $card_data ): bool {

		if ( ! is_user_logged_in() ) {
			return false;
		}

		$user_id = get_current_user_id();

		$args = array(
			'MerID'       => $this->gateway->get_mer_id(),
			'MerTradeNo'  => time(),
			'TradeAmt'    => 5,
			'Timestamp'   => time(),
			'UsrMail'     => get_userdata( $user_id )->user_email,
			'ProdDesc'    => '新增信用卡',
			'CardNo'      => $card_data['number'],
			'CardExpired' => $card_data['expiry'],
			'CardCVC'     => $card_data['cvc'],
			'CreditToken' => get_userdata( $user_id )->user_email,
		);

		$parameter = array(
			'MerID'       => $this->gateway->get_mer_id(),
			'Version'     => '1.0',
			'EncryptInfo' => \Payuni\APIs\Payment::encrypt( $args ),
			'HashInfo'    => \Payuni\APIs\Payment::hash_info( \Payuni\APIs\Payment::encrypt( $args ) ),
		);

		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'body'    => $parameter,
		);

		$response = wp_remote_request( $this->gateway->get_api_url() . $this->gateway->get_api_endpoint_url(), $options );
		$resp     = json_decode( wp_remote_retrieve_body( $response ) );

		return Response::hash_response( $resp );
	}

	private function get_card_hash( $order ) {
		$parent_order  = '';
		$subscriptions = wcs_get_subscriptions_for_order( $order->get_id(), array( 'order_type' => 'any' ) );
		if ( $subscriptions ) {
			foreach ( $subscriptions as $subscription_obj ) {
				$parent_order = wc_get_order( $subscription_obj->get_parent_id() );
			}

			$token_id = $parent_order->get_meta( '_payuni_token_id' );

			if ( ! $token_id || 'new' === $token_id ) {
				return $parent_order->get_meta( '_payuni_card_hash' );
			}

			$token = \WC_Payment_Tokens::get( $parent_order->get_meta( '_payuni_token_id' ) );

			return $token->get_token();
		}

		return '';
	}

	private function set_response( $payment_method, $resp ) {
		switch ( $payment_method ) {
			case 'payuni-credit':
			case 'payuni-credit-installment':
			case 'payuni-credit-subscription':
				Response::card_response( $resp );
				break;
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
