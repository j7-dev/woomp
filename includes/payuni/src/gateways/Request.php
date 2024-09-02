<?php
/**
 * Payuni_Payment_Request class file
 *
 * @package payuni
 */

namespace PAYUNI\Gateways;

use WC_Order;
use PAYUNI\APIs\Payment;

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
	 * 信用卡號付款請求
	 * 訂閱的第一次付款其實也是走這邊
	 *
	 * @param \WC_Order                                                                     $order The order object.
	 * @param ?array{number:string, expiry:string, cvc:string, token_id:string, new:string} $card_data 卡片資料
	 *
	 * @return array{result:string, redirect:string, status_code:string, order_id:int}
	 */
	public function build_request( WC_Order $order, $card_data = null ): array {

		$body_params = $this->get_transaction_args( $order, $card_data );
		$order_id    = $order->get_id();
		$options     = [
			'method'  => 'POST',
			'timeout' => 60,
			'body'    => $body_params,
		];

		$response = \wp_remote_request(
			$this->gateway->get_api_url() . $this->gateway->get_api_endpoint_url(),
			$options
		);

		$resp = json_decode( \wp_remote_retrieve_body( $response ) );

		//@codingStandardsIgnoreStart
		$data = \Payuni\APIs\Payment::decrypt( $resp->EncryptInfo );
		//@codingStandardsIgnoreEnd

		unset( $data['Card6No'] ); // remove card number from log.

		Payment::log( $data );

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
			if ('CREDIT04001' !== $data['Status']) {
				// "已存在相同商店訂單編號" 已經用新建訂單解決，不用顯示錯誤
				\wc_add_notice( $data['Message'], 'error' );
			}

			return [
				'result'      => 'failed',
				'redirect'    => $this->gateway->get_return_url( $order ),
				'status_code' => $data['Status'],
				'order_id'    => $order_id,
			];
		}

		// 3D 驗證走以下判斷，會 redirect 到 $data['URL'] 去做 3D 驗證
		if ( $is_3d_auth ) {
			return [
				'result'      => 'success',
				'redirect'    => $data['URL'],
				'status_code' => $data['Status'],
				'order_id'    => $order_id,
			];
		}

		$this->set_response( $order->get_payment_method(), $resp );

		return [
			'result'      => 'success',
			'redirect'    => $this->gateway->get_return_url( $order ),
			'status_code' => $data['Status'],
			'order_id'    => $order_id,
		];
	}

	/**
	 * Build transaction args.
	 *
	 * @see https://www.payuni.com.tw/docs/web/#/7/35
	 *
	 * @param \WC_Order                                                                     $order The order object.
	 * @param ?array{number:string, expiry:string, cvc:string, token_id:string, new:string} $card_data 卡片資料
	 *
	 * @return array{MerID:string, Version:string, EncryptInfo:string, HashInfo:string}
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
			$token_id      = $card_data['token_id'] ?? ''; // 可能是 數字、new
			$save_new_card = $card_data['new'] ?? false;
			if ('payuni-credit-subscription' === $this->gateway->id) {
				// 如果是訂閱收款就一定會存卡號
				$save_new_card = 'yes';
			}

			// 不判斷 token_id 直接傳卡號
			$args['CardNo']      = $card_data['number'];
			$args['CardExpired'] = $card_data['expiry'];
			$args['CardCVC']     = $card_data['cvc'];

			// 只有要新增卡號才需要傳 CreditToken
			// TODO 這邊要再確認一下，因為在 subscription 那邊，都固定有傳，所以猜想，如果使用已存 token 付款也要傳
			if ( $save_new_card || \is_numeric($token_id) ) {
				$args['CreditToken'] = $order->get_billing_email(); // CreditToken 就是傳給金流公司，用來取得 token (CreditHash) 的憑證，這邊以客戶的 email 作為識別
			}

			// 信用卡分期數
			if ( !empty($card_data['period'])) {
				$args['CardInst'] = $card_data['period'];
			}

			// 判斷是否為使用 token (CreditHash) 付款
			if ( \is_numeric($token_id)) {
				$token              = \WC_Payment_Tokens::get( $token_id );
				$args['CreditHash'] = $token->get_token();

				// 如果有 CreditHash 就不需要再傳卡號、有效期、末三碼
				unset( $args['CardNo'] );
				unset( $args['CardExpired'] );
				unset( $args['CardCVC'] );
			}

			// 是否開啟 3D 驗證
			if ( \wc_string_to_bool( \get_option( 'payuni_3d_auth', 'yes' ) ) ) {
				$args['API3D'] = 1;
				// $data[ 'NotifyURL' ] = home_url('wc-api/payuni_notify_card');
				$args['ReturnURL'] = \home_url( 'wc-api/payuni_notify_card' );
				$order->update_meta_data( '_payuni_is_3d_auth', 'yes' );
			}

			// 儲存 meta 資料在 order 上
			$order->update_meta_data( '_payuni_token_id', $token_id );

			$order->update_meta_data( '_payuni_token_maybe_save', $save_new_card ); // □ 儲存付款資訊，下次付款更方便的 checkbox
			$order->save();
		}

		$args = apply_filters(
			'payuni_transaction_args_' . $this->gateway->id,
			$args,
			$order,
			$card_data
		);

		$parameter                = [];
		$parameter['MerID']       = $this->gateway->get_mer_id();
		$parameter['Version']     = '1.0';
		$parameter['EncryptInfo'] = \Payuni\APIs\Payment::encrypt( $args );
		$parameter['HashInfo']    = \Payuni\APIs\Payment::hash_info( $parameter['EncryptInfo'] );

		return $parameter;
	}

	/**
	 * Get product name
	 *
	 * @param \WC_Order $order The order object.
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
	 * 未來如果用 get_transaction_args 取得參數，記得，不需要 3D 驗證
	 *
	 * @param int       $amount The amount.
	 * @param \WC_Order $order The subscription order object.
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

		Payment::log( $args );

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
		// 如果沒有啟用訂閱，就不初始化卡片管理
		if (!class_exists('WC_Subscriptions')) {
			return;
		}
		$parent_order  = '';
		$subscriptions = \wcs_get_subscriptions_for_order( $order->get_id(), [ 'order_type' => 'any' ] );
		if ( $subscriptions ) {
			foreach ( $subscriptions as $subscription_obj ) {
				// 上層訂單
				$parent_order = \wc_get_order( $subscription_obj->get_parent_id() );
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
	 * 取得 card hash 的付款請求
	 * 用戶可能只是儲存付款方式，沒有訂單，這邊會接受一個動態產生的訂單
	 *
	 * @see https://www.payuni.com.tw/docs/web/#/7/35
	 *
	 * @param \WC_Order                                                                    $order The order object.
	 * @param array{number:string, expiry:string, cvc:string, token_id:string, new:string} $card_data 卡片資料
	 *
	 * @return array
	 */
	public function build_hash_request( $order, array $card_data ): array {
		$order_suffix = ( $order->get_meta( '_payuni_order_suffix' ) ) ? '-' . $order->get_meta( '_payuni_order_suffix' ) : '';

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
			$order->update_meta_data( '_payuni_is_3d_auth', 'yes' );
			$order->save();
		}

		Payment::log( $args );

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

			// 更新訂單狀態為已完成.
			$order->update_status( 'completed' );
			$order->save();

		}

		return $result;
	}
}
