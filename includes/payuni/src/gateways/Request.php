<?php

/**
 * Payuni_Payment_Request class file
 *
 * @package payuni
 */

namespace PAYUNI\Gateways;

use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Generates payment form and redirect to payuni
 */
final class Request {


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
	 * Generate the form and redirect to PayNow
	 * 訂閱的第一次付款其實也是走這邊
	 *
	 * @param WC_Order $order The order object.
	 *
	 * @return array
	 */
	public function build_request( WC_Order $order, $card_data = null ): array {
		$body_params = $this->get_transaction_args( $order, $card_data );

		$options = [
			'method'  => 'POST',
			'timeout' => 60,
			'body'    => $body_params,
		];

		$response = wp_remote_request(
			$this->gateway->get_api_url() . $this->gateway->get_api_endpoint_url(),
			$options
		);
		$resp     = json_decode( wp_remote_retrieve_body( $response ) );

		//@codingStandardsIgnoreStart
		$data = \Payuni\APIs\Payment::decrypt( $resp->EncryptInfo );
		//@codingStandardsIgnoreEnd

		unset( $data['Card6No'] ); // remove card number from log.

		// Payment::log( $data, 'request' ); 因為呼叫層級錯誤，所以先註解掉

		/*
		有開 3D 驗證的 response
		["Status"]=> "SUCCESS"
		["Message"]=> "建立幕後3D成功"
		["URL"]=> "https://api.payuni.com.tw/api/credit/api_3d/1711111054055003344"
		*/

		[
			'status'            => $status,
			'card_4no'          => $card_4no,
			'card_hash'         => $card_hash,
			'card_expiry_month' => $card_expiry_month,
			'card_expiry_year'  => $card_expiry_year,
			'is_3d_auth'        => $is_3d_auth,
		] = Response::get_formatted_decrypted_data( $data );

		// 結帳頁顯示錯誤訊息.
		if ( 'SUCCESS' !== $status ) {
			\wc_add_notice( $data['Message'], 'error' );

			return [
				'result'   => 'failed',
				'redirect' => $this->gateway->get_return_url( $order ),
			];
		}

		// 3D 驗證走以下判斷，會 redirect 到 $data['URL'] 去做 3D 驗證
		if ( $is_3d_auth ) {
			return [
				'result'   => 'success',
				'redirect' => $data['URL'],
			];
		}

		$this->set_response( $order->get_payment_method(), $resp );

		return [
			'result'   => 'success',
			'redirect' => $this->gateway->get_return_url( $order ),
		];
	}

	/**
	 * Build transaction args.
	 *
	 * @param WC_Order $order The order object.
	 * @param ?array   $card_data The card data.
	 *
	 * @return array
	 */
	public function get_transaction_args( WC_Order $order, ?array $card_data ): array {
		$order_suffix = ( $order->get_meta( '_payuni_order_suffix' ) ) ? '-' . $order->get_meta( '_payuni_order_suffix' ) : '';

		$args = [
			'MerID'      => $this->gateway->get_mer_id(),
			'MerTradeNo' => $order->get_id() . $order_suffix,
			'TradeAmt'   => $order->get_total(),
			'Timestamp'  => time(),
			'UsrMail'    => $order->get_billing_email(),
			'ProdDesc'   => $this->get_product_name( $order ),
		];

		if ( $card_data ) {
			$order->update_meta_data( '_payuni_token_id', $card_data['token_id'] );
			// 是否新增付款方式，存入帳號
			$order->update_meta_data( '_payuni_token_maybe_save', $card_data['new'] );
			$order->save();

			// 不判斷 token_id 直接傳卡號
			$data = [
				'CardNo'      => $card_data['number'],
				'CardExpired' => $card_data['expiry'],
				'CardCVC'     => $card_data['cvc'],
			];

			if ( $card_data['new'] ?? false ) {
				$data['CreditToken'] = $order->get_billing_email();
			}

			if ( isset( $card_data['period'] ) ) {
				$data['CardInst'] = $card_data['period'];
			}

			$args = array_merge(
				$args,
				$data
			);
		}

		$args = apply_filters(
			'payuni_transaction_args_' . $this->gateway->id,
			$args,
			$order,
			$card_data
		);

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

	/**
	 * The request for subscription.
	 * 訂閱的定期扣款走這邊
	 *
	 * @param int      $amount The amount.
	 * @param WC_Order $order The subscription order object.
	 */
	public function build_subscription_request( int $amount, WC_Order $order ): void {
		$order_suffix = ( $order->get_meta( '_payuni_order_suffix' ) ) ? '-' . $order->get_meta( '_payuni_order_suffix' ) : '';

		$args = [
			'MerID'       => $this->gateway->get_mer_id(),
			'MerTradeNo'  => $order->get_id() . $order_suffix,
			'TradeAmt'    => $amount,
			'Timestamp'   => time(),
			'UsrMail'     => $order->get_billing_email(),
			'ProdDesc'    => $this->get_product_name( $order ),
			'CreditToken' => $order->get_billing_email(),
			'CreditHash'  => $this->get_card_hash( $order ),
		];

		$parameter = [
			'MerID'       => $this->gateway->get_mer_id(),
			'Version'     => '1.0',
			'EncryptInfo' => \Payuni\APIs\Payment::encrypt( $args ),
			'HashInfo'    => \Payuni\APIs\Payment::hash_info( \Payuni\APIs\Payment::encrypt( $args ) ),
		];

		$options = [
			'method'  => 'POST',
			'timeout' => 60,
			'body'    => $parameter,
		];

		$response = wp_remote_request(
			$this->gateway->get_api_url() . $this->gateway->get_api_endpoint_url(),
			$options
		);
		$resp     = json_decode( wp_remote_retrieve_body( $response ) );

		$this->set_response( $order->get_payment_method(), $resp );
	}

	private function get_card_hash( $order ) {
		$parent_order  = '';
		$subscriptions = wcs_get_subscriptions_for_order( $order->get_id(), [ 'order_type' => 'any' ] );
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

	/**
	 * The request for get card hash without order.
	 *
	 * @param WC_Order|null $order The order object.
	 * @param array         $card_data The card data.
	 *
	 * @return array
	 */
	public function build_hash_request( $order, array $card_data ): array {
		if ( ! ! $order ) {
			$order_suffix = ( $order->get_meta( '_payuni_order_suffix' ) ) ? '-' . $order->get_meta( '_payuni_order_suffix' ) : '';
		} else {
			$min = 0;
			$max = 99999;

			$random_string = mt_rand( $min, $max );
			$order_suffix  = 'add_payment_method_' . $random_string;
		}

		if ( ! is_user_logged_in() ) {
			return false;
		}

		$user_id = get_current_user_id();

		$args = [
			'MerID'       => $this->gateway->get_mer_id(),
			'MerTradeNo'  => $order?->get_id() . $order_suffix,
			'TradeAmt'    => 5,
			'Timestamp'   => time(),
			'UsrMail'     => get_userdata( $user_id )->user_email,
			'ProdDesc'    => '新增信用卡',
			'CardNo'      => $card_data['number'],
			'CardExpired' => $card_data['expiry'],
			'CardCVC'     => $card_data['cvc'],
			'CreditToken' => get_userdata( $user_id )->user_email,
		];

		if ( wc_string_to_bool( get_option( 'payuni_3d_auth', 'yes' ) ) ) {
			$args['API3D'] = 1;
			// $data[ 'NotifyURL' ] = home_url('wc-api/payuni_notify_card');
			$args['ReturnURL'] = home_url( 'wc-api/payuni_notify_card' );
		}

		$parameter = [
			'MerID'       => $this->gateway->get_mer_id(),
			'Version'     => '1.0',
			'EncryptInfo' => \Payuni\APIs\Payment::encrypt( $args ),
			'HashInfo'    => \Payuni\APIs\Payment::hash_info( \Payuni\APIs\Payment::encrypt( $args ) ),
		];

		$options = [
			'method'  => 'POST',
			'timeout' => 60,
			'body'    => $parameter,
		];

		$response = wp_remote_request(
			$this->gateway->get_api_url() . $this->gateway->get_api_endpoint_url(),
			$options
		);
		$resp     = json_decode( wp_remote_retrieve_body( $response ) );

		$redirect = $order ? $this->gateway->get_return_url( $order ) : '';
		$result   = Response::handle_response( $resp, $redirect );

		// 如果是新增付款方式，就不需要再傳卡號、有效期、末三碼
		if ( 'success' === $result['result'] && ! $result['is_3d_auth'] ) {
			if ( $order ) {
				// 更新訂單狀態為已完成.
				$order->update_status( 'completed' );
				$order->save();
			}
		}

		return $result;
	}
}
