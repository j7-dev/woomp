<?php
/**
 * Payuni_Payment_Response class file
 *
 * @package Payuni
 */

namespace PAYUNI\Gateways;

use Payuni\APIs\Payment;

defined( 'ABSPATH' ) || exit;

/**
 * Receive response from Payuni.
 */
class Response {

	/**
	 * Class instance
	 *
	 * @var Response
	 */
	private static $instance;

	/**
	 * Constructor
	 */
	public function __construct() {
		// do nothing.
	}

	/**
	 * Get the single instance or new one if not exists.
	 *
	 * @return Response
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize and add hooks
	 *
	 * @return void
	 */
	public static function init() {
		$class = self::get_instance();
		add_action( 'woocommerce_api_payuni_notify_card', array( $class, 'card_response' ) );
		add_action( 'woocommerce_api_payuni_notify_atm', array( $class, 'atm_response' ) );
		add_action( 'woocommerce_api_payuni_notify_cvs', array( $class, 'cvs_response' ) );
	}

	/**
	 * Receive response from Payuni
	 * 帶有 $resp 是走 3D 驗證的幕後，帶有 $_REQUEST 是結帳頁直接回傳
	 *
	 * @return void
	 */
	public static function card_response( $resp = null ): void {

		global $woocommerce;

		$encrypt_info = ( $resp ) ? $resp->EncryptInfo : $_REQUEST['EncryptInfo'];

		$data = Payment::decrypt( $encrypt_info );

		unset( $data['Card6No'] ); // remove card number from log.

		Payment::log( $data, 'response' );

		$order    = wc_get_order( explode( '-', $data['MerTradeNo'] )[0] );
		$status   = $data['Status'];
		$message  = $data['Message'];
		$trade_no = $data['TradeNo'];

		$order->update_meta_data( '_payuni_order_suffix', (int) $order->get_meta( '_payuni_order_suffix' ) + 1 );

		$order->update_meta_data( '_payuni_resp_status', $status );
		$order->update_meta_data( '_payuni_resp_message', $message );
		$order->update_meta_data( '_payuni_resp_trade_no', $trade_no );

		// 處理信用卡.
		$card_bank         = $data['CardBank'];
		$card_bank_name    = $data['AuthBankName'];
		$card_4no          = $data['Card4No'];
		$card_hash         = $data['CreditHash'];
		$card_expiry_month = substr( $data['CreditLife'], 0, 2 );
		$card_expiry_year  = '20' . substr( $data['CreditLife'], 2, 2 );

		$card_inst = $data['CardInst'];
		$each_amt  = $data['EachAmt'];
		$first_amt = $data['FirstAmt'];

		$order->update_meta_data( '_payuni_resp_card_bank', "({$card_bank}){$card_bank_name}" );
		$order->update_meta_data( '_payuni_card_number', $card_4no );
		$order->update_meta_data( '_payuni_resp_card_inst', $card_inst );
		$order->update_meta_data( '_payuni_resp_first_amt', $first_amt );
		$order->update_meta_data( '_payuni_resp_each_amt', $each_amt );

		$order->add_order_note( "<strong>統一金流交易紀錄</strong><br>狀態碼：{$status}<br>交易訊息：{$message}<br>交易編號：{$trade_no}<br>卡號末四碼：{$card_4no}", true );

		if ( 'SUCCESS' === $status ) {
			$user_id  = $order->get_customer_id();
			$method   = $order->get_meta( '_payment_method' );
			$remember = $order->get_meta( '_' . $method . '-card_remember' );

			if ( $user_id && $remember ) {
				update_user_meta( $user_id, '_' . $method . '_4no', $card_4no );
				update_user_meta( $user_id, '_' . $method . '_hash', $card_hash );
			}
			$order->update_status( 'processing' );

			// 設定 WC_Payment_Tokens.
			if ( 'payuni-credit-subscription' === $method ) {
				$order->update_meta_data( '_payuni_card_hash', $card_hash );

				if ( $order->get_meta( '_payuni_token_maybe_save' ) ) {
					$token = new \WC_Payment_Token_CC();
					$token->set_token( $card_hash );
					$token->set_gateway_id( $method );
					$token->set_card_type( 'visa' );
					$token->set_last4( $card_4no );
					$token->set_expiry_month( $card_expiry_month );
					$token->set_expiry_year( $card_expiry_year );
					$token->set_user_id( $order->get_customer_id() );
					$token->save();
				}
			}
		} else {
			$order->update_status( 'failed' );
		}

		if ( $woocommerce->cart ) {
			$woocommerce->cart->empty_cart();
		}

		$order->save();

		// 0 元訂閱訂單要做退款.
		if ( 0 === (int) \WC_Subscriptions_Order::get_total_initial_payment( $order ) ) {

			$data = array(
				'nonce'   => wp_create_nonce( 'payuni_refund' ),
				'order'   => $order,
				'amount'  => 5,
				'user_id' => $order->get_customer_id(),
			);

			$refund = new Refund();
			$refund->card_refund( $data, true );
		}

		if ( ! $resp ) {
			wp_safe_redirect( $order->get_checkout_order_received_url() );
			exit;
		}

	}

	public static function hash_response( $resp = null ) {
		$encrypt_info = ( $resp ) ? $resp->EncryptInfo : $_REQUEST['EncryptInfo'];

		$data              = Payment::decrypt( $encrypt_info );
		$status            = $data['Status'];
		$card_4no          = $data['Card4No'];
		$card_hash         = $data['CreditHash'];
		$card_expiry_month = substr( $data['CreditLife'], 0, 2 );
		$card_expiry_year  = '20' . substr( $data['CreditLife'], 2, 2 );

		// $data = array(
		// 'nonce'   => wp_create_nonce( 'payuni_refund' ),
		// 'order'   => $order,
		// 'amount'  => 5,
		// 'user_id' => get_current_user_id(),
		// );
		//
		// $refund = new Refund();
		// $refund->card_refund( $data, true );

		if ( 'SUCCESS' === $status ) {
			$token = new \WC_Payment_Token_CC();
			$token->set_token( $card_hash );
			$token->set_gateway_id( 'payuni-credit-subscription' );
			$token->set_card_type( 'visa' );
			$token->set_last4( $card_4no );
			$token->set_expiry_month( $card_expiry_month );
			$token->set_expiry_year( $card_expiry_year );
			$token->set_user_id( get_current_user_id() );
			$token->save();

			return true;
		}

		return false;
	}

	/**
	 * Receive response from Payuni atm payment
	 *
	 * @param object $resp payuni response.
	 *
	 * @return void
	 */
	public static function atm_response( $resp ) {

		// 背景通知付款結果.
		if ( $_REQUEST['Status'] ) {
			if ( 'SUCCESS' === $_REQUEST['Status'] ) {
				$data  = Payment::decrypt( $_REQUEST['EncryptInfo'] );
				$time  = date( 'Y-m-d H:i:s', time() );
				$order = wc_get_order( $data['MerTradeNo'] );
				$order->update_status( 'processing' );
				$order->add_order_note( "<strong>統一金流繳費紀錄</strong><br>狀態碼：{$data['Status']}<br>繳費結果：{$data['Message']}<br>繳費時間：{$data['PayTime']}<br>轉帳後五碼：{$data['Account5No']}", true );
				$order->save();
			}
		}

		// 付款完成取號.
		if ( $resp ) {
			global $woocommerce;
			$encrypt_info = $resp->EncryptInfo;
			$data         = Payment::decrypt( $encrypt_info );
			$status       = $data['Status'];
			$message      = $data['Message'];
			$trade_no     = $data['TradeNo'];
			$bank         = '(' . $data['BankType'] . ')' . Payment::get_bank_name( $data['BankType'] );
			$bank_no      = $data['PayNo'];
			$bank_expire  = date( 'Y-m-d H:i:s', strtotime( $data['ExpireDate'] ) );

			$order = wc_get_order( $data['MerTradeNo'] );
			$order->update_meta_data( '_payuni_resp_status', $status );
			$order->update_meta_data( '_payuni_resp_message', $message );
			$order->update_meta_data( '_payuni_resp_trade_no', $trade_no );
			$order->update_meta_data( '_payuni_resp_bank', $bank );
			$order->update_meta_data( '_payuni_resp_bank_no', $bank_no );
			$order->update_meta_data( '_payuni_resp_bank_expire', $bank_expire );

			$order->add_order_note( "<strong>統一金流交易紀錄</strong><br>狀態碼：{$status}<br>交易訊息：{$message}<br>交易編號：{$trade_no}<br>轉帳銀行：{$bank}<br>轉帳帳號：${bank_no}<br>轉帳期限：{$bank_expire}", true );

			if ( 'SUCCESS' === $status ) {
				$order->update_status( 'pending' );
			} else {
				$order->update_status( 'failed' );
			}

			// 超過繳費期限取消訂單.
			as_schedule_single_action( strtotime( $bank_expire . '-8 hour' ), 'payuni_atm_check', array( $data['MerTradeNo'] ) );

			$woocommerce->cart->empty_cart();
			$order->save();
		}
	}

	/**
	 * Receive response from Payuni cvs payment
	 *
	 * @param object $resp payuni response.
	 *
	 * @return void
	 */
	public static function cvs_response( $resp ) {

		// 背景通知付款結果.
		if ( $_REQUEST['Status'] ) {
			if ( 'SUCCESS' === $_REQUEST['Status'] ) {
				$data  = Payment::decrypt( $_REQUEST['EncryptInfo'] );
				$time  = date( 'Y-m-d H:i:s', time() );
				$order = wc_get_order( $data['MerTradeNo'] );
				$order->update_status( 'processing' );
				$order->add_order_note( "<strong>統一金流繳費紀錄</strong><br>狀態碼：{$data['Status']}<br>繳費結果：{$data['Message']}<br>繳費時間：{$data['PayTime']}<br>轉帳後五碼：{$data['Account5No']}", true );
				$order->save();
			}
		}

		// 付款完成取號.
		if ( $resp ) {
			global $woocommerce;
			$encrypt_info = $resp->EncryptInfo;
			$data         = Payment::decrypt( $encrypt_info );

			Payment::log( $data );

			return;

			$status      = $data['Status'];
			$message     = $data['Message'];
			$trade_no    = $data['TradeNo'];
			$bank        = '(' . $data['BankType'] . ')' . Payment::get_bank_name( $data['BankType'] );
			$bank_no     = $data['PayNo'];
			$bank_expire = date( 'Y-m-d H:i:s', strtotime( $data['ExpireDate'] ) );

			$order = wc_get_order( $data['MerTradeNo'] );
			$order->update_meta_data( '_payuni_resp_status', $status );
			$order->update_meta_data( '_payuni_resp_message', $message );
			$order->update_meta_data( '_payuni_resp_trade_no', $trade_no );
			$order->update_meta_data( '_payuni_resp_bank', $bank );
			$order->update_meta_data( '_payuni_resp_bank_no', $bank_no );
			$order->update_meta_data( '_payuni_resp_bank_expire', $bank_expire );

			$order->add_order_note( "<strong>統一金流交易紀錄</strong><br>狀態碼：{$status}<br>交易訊息：{$message}<br>交易編號：{$trade_no}<br>轉帳銀行：{$bank}<br>轉帳帳號：${bank_no}<br>轉帳期限：{$bank_expire}", true );

			if ( 'SUCCESS' === $status ) {
				$order->update_status( 'on-hold' );
			} else {
				$order->update_status( 'failed' );
			}

			// 超過繳費期限取消��單.
			as_schedule_single_action( strtotime( $bank_expire . '-8 hour' ), 'payuni_cvs_check', array( $data['MerTradeNo'] ) );

			$woocommerce->cart->empty_cart();
			$order->save();
		}
	}

	public static function subscription_response( $resp ) {

	}
}

Response::init();
