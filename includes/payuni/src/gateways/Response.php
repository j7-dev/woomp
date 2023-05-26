<?php
/**
 * Payuni_Payment_Response class file
 *
 * @package Payuni
 */

 namespace PAYUNI\Gateways;

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
		add_action( 'wp_ajax_payuni_refund', array( $class, 'card_refund' ) );
		add_action( 'woocommerce_api_payuni_notify_atm', array( $class, 'atm_response' ) );
		add_action( 'woocommerce_api_payuni_notify_cvs', array( $class, 'cvs_response' ) );
	}

	/**
	 * Receive response from Payuni
	 *
	 * @return void
	 */
	public static function card_response() {

		// 背景通知付款結果.

		global $woocommerce;

		$data = \Payuni\APIs\Payment::decrypt( $_REQUEST['EncryptInfo'] );

		\PAYUNI\APIs\Payment::log( $data );

		$order    = wc_get_order( explode( '-', $data['MerTradeNo'] )[0] );
		$status   = $data['Status'];
		$message  = $data['Message'];
		$trade_no = $data['TradeNo'];

		if ( $order->get_meta( '_payuni_resp_status' ) ) {
			wp_safe_redirect( $order->get_checkout_order_received_url() );
			exit;
		} else {
			$order->update_meta_data( '_payuni_resp_status', $status );
			$order->update_meta_data( '_payuni_resp_message', $message );
			$order->update_meta_data( '_payuni_resp_trade_no', $trade_no );

			// 處理信用卡.
			$card_bank      = $data['CardBank'];
			$card_bank_name = $data['AuthBankName'];
			$card_4no       = $data['Card4No'];
			$card_hash      = $data['CreditHash'];

			$card_inst = $data['CardInst'];
			$first_amt = $data['FirstAmt'];
			$each_amt  = $data['EachAmt'];

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
			} else {
				$order->update_meta_data( '_payuni_order_suffix', (int) $order->get_meta( '_payuni_order_suffix' ) + 1 );
				$order->update_status( 'failed' );
			}

			$woocommerce->cart->empty_cart();
			$order->save();

			wp_safe_redirect( $order->get_checkout_order_received_url() );
			exit;
		}

	}

	/**
	 * Receive response from Payuni atm payment
	 *
	 * @param object $resp payuni response.
	 * @return void
	 */
	public static function atm_response( $resp ) {

		// 背景通知付款結果.
		if ( $_REQUEST['Status'] ) {
			if ( 'SUCCESS' === $_REQUEST['Status'] ) {
				$data  = \Payuni\APIs\Payment::decrypt( $_REQUEST['EncryptInfo'] );
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
			$data         = \Payuni\APIs\Payment::decrypt( $encrypt_info );
			$status       = $data['Status'];
			$message      = $data['Message'];
			$trade_no     = $data['TradeNo'];
			$bank         = '(' . $data['BankType'] . ')' . \PAYUNI\APIs\Payment::get_bank_name( $data['BankType'] );
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
	 * @return void
	 */
	public static function cvs_response( $resp ) {

		// 背景通知付款結果.
		if ( $_REQUEST['Status'] ) {
			if ( 'SUCCESS' === $_REQUEST['Status'] ) {
				$data  = \Payuni\APIs\Payment::decrypt( $_REQUEST['EncryptInfo'] );
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
			$data         = \Payuni\APIs\Payment::decrypt( $encrypt_info );

			\PAYUNI\APIs\Payment::log( $data );
			return;

			$status      = $data['Status'];
			$message     = $data['Message'];
			$trade_no    = $data['TradeNo'];
			$bank        = '(' . $data['BankType'] . ')' . \PAYUNI\APIs\Payment::get_bank_name( $data['BankType'] );
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

			// 超過繳費期限取消訂單.
			as_schedule_single_action( strtotime( $bank_expire . '-8 hour' ), 'payuni_cvs_check', array( $data['MerTradeNo'] ) );

			$woocommerce->cart->empty_cart();
			$order->save();
		}
	}

	public static function card_refund() {

		$nonce = $_POST['nonce'];
		if ( ! wp_verify_nonce( $nonce, 'payuni_refund' ) ) {
			echo wp_json_encode( 'nonce退款發生錯誤，請聯繫管理員!' );
			die();
		}

		$order       = \wc_get_order( $_POST['order_id'] );
		$amount      = $_POST['amount'];
		$customer_id = (int) $order->get_customer_id();
		$user_id     = (int) $_POST['user_id'];

		if ( $customer_id !== $user_id ) {
			echo wp_json_encode( 'userid退款發生錯誤，請聯繫管理員!' );
			die();
		}

		$order_status = $order->get_status();

		if ( ! in_array( 'wc-' . $order_status, get_option( 'payuni_admin_refund' ) ) ) {
			$order->add_order_note( '<strong>統一金流退費紀錄</strong><br>退費結果：該訂單狀態不允許退費', true );
			echo wp_json_encode( '該訂單狀態不允許退費' );
			die();
		}

		$args = array(
			'MerID'     => ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? get_option( 'payuni_payment_merchant_no_test' ) : get_option( 'payuni_payment_merchant_no' ),
			'TradeNo'   => $order->get_meta( '_payuni_resp_trade_no' ),
			'TradeAmt'  => $amount,
			'Timestamp' => time(),
			'CloseType' => 2,
		);

		$parameter['MerID']       = ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? get_option( 'payuni_payment_merchant_no_test' ) : get_option( 'payuni_payment_merchant_no' );
		$parameter['Version']     = '1.0';
		$parameter['EncryptInfo'] = \Payuni\APIs\Payment::encrypt( $args );
		$parameter['HashInfo']    = \Payuni\APIs\Payment::hash_info( $parameter['EncryptInfo'] );

		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'body'    => $parameter,
		);

		$url     = ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? 'https://sandbox-api.payuni.com.tw/' : 'https://api.payuni.com.tw/';
		$request = wp_remote_request( $url . 'api/trade/close', $options );
		$resp    = json_decode( wp_remote_retrieve_body( $request ) );
		$data    = \Payuni\APIs\Payment::decrypt( $resp->EncryptInfo );

		if ( 'SUCCESS' === $data['Status'] ) {
			$note = '<strong>統一金流退費紀錄</strong><br>退費結果：' . $data['Message'];
			if ( $reason ) {
				$note .= '<br>退費原因：' . $reason;
			}
			$order->add_order_note( $note, true );
			$order->update_status( 'refunded' );
			$order->save();
			echo wp_json_encode( $data['Message'] );
			die();
		} else {
			$note = '<strong>統一金流退費紀錄</strong><br>退費結果：' . $data['Message'];
			$order->add_order_note( $note, true );
			$order->save();
			echo wp_json_encode( $data['Message'] );
			die();
		}
	}
}

Response::init();
