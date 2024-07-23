<?php

namespace PAYUNI\Gateways;

use PAYUNI\APIs\Payment;

defined( 'ABSPATH' ) || exit;

class Refund {

	/**
	 * Initialize class and add hooks
	 *
	 * @return void
	 */
	public static function init() {
		$class = new self();
		\add_action( 'payuni_cancel_trade_by_order', array( $class, 'cancel_trade_by_order' ) );
		\add_action( 'payuni_cancel_trade_by_trade_no', array( $class, 'cancel_trade_by_trade_no' ), 10, 2 );
		\add_action( 'woocommerce_order_status_changed', array( $class, 'handle_refund' ), 10, 3 );
	}

	/**
	 * Handle refund
	 * User can trigger refund in Front-End My Account page
	 */
	public function refund_by_order( \WC_Order $order ): array {
		$trade_no = $order->get_meta( '_payuni_resp_trade_no' );
		$amount   = (int) $order->get_total();

		$args = array(
			'MerID'     => ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? get_option( 'payuni_payment_merchant_no_test' ) : get_option( 'payuni_payment_merchant_no' ),
			'TradeNo'   => $trade_no,
			'TradeAmt'  => $amount,
			'Timestamp' => time(),
			'CloseType' => 2,
		);

		$parameter['MerID']       = ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? get_option( 'payuni_payment_merchant_no_test' ) : get_option( 'payuni_payment_merchant_no' );
		$parameter['Version']     = '1.0';
		$parameter['EncryptInfo'] = Payment::encrypt( $args );
		$parameter['HashInfo']    = Payment::hash_info( $parameter['EncryptInfo'] );

		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'body'    => $parameter,
		);

		$url     = ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? 'https://sandbox-api.payuni.com.tw/' : 'https://api.payuni.com.tw/';
		$request = wp_remote_request( $url . 'api/trade/close', $options );
		$resp    = json_decode( wp_remote_retrieve_body( $request ) );
		$data    = Payment::decrypt( $resp->EncryptInfo );

		return $data;
	}

	/**
	 * 取消交易授權 by order
	 * 當交易的 CloseStatus 為 7=請款處理中 時，只能用取消授權的方式退款
	 *
	 * @see https://github.com/j7-dev/woomp/issues/20
	 * @param \WC_Order $order
	 *
	 * @return void
	 */
	public function cancel_trade_by_order( \WC_Order $order ): array {
		$trade_no = $order->get_meta( '_payuni_resp_trade_no' );
		$args     = array(
			'MerID'     => ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? get_option( 'payuni_payment_merchant_no_test' ) : get_option( 'payuni_payment_merchant_no' ),
			'TradeNo'   => $trade_no,
			'Timestamp' => time(),
		);

		$parameter['MerID']       = ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? get_option( 'payuni_payment_merchant_no_test' ) : get_option( 'payuni_payment_merchant_no' );
		$parameter['Version']     = '1.0';
		$parameter['EncryptInfo'] = Payment::encrypt( $args );
		$parameter['HashInfo']    = Payment::hash_info( $parameter['EncryptInfo'] );

		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'body'    => $parameter,
		);

		$url     = ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? 'https://sandbox-api.payuni.com.tw/' : 'https://api.payuni.com.tw/';
		$request = wp_remote_request( "{$url}api/trade/cancel", $options );
		$resp    = json_decode( wp_remote_retrieve_body( $request ) );
		$data    = Payment::decrypt( $resp->EncryptInfo );
		return $data;
	}

	/**
	 * 取消交易授權 by trade_no
	 * 定期定額退款 5 元，取消授權，不是真的退款，比較像取消授權交易
	 *
	 * @see https://github.com/j7-dev/woomp/issues/20
	 * @param string $trade_no
	 * @param int    $order_id
	 *
	 * @return void
	 */
	public function cancel_trade_by_trade_no( string $trade_no, $order_id ): array {
		$args = array(
			'MerID'     => ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? get_option( 'payuni_payment_merchant_no_test' ) : get_option( 'payuni_payment_merchant_no' ),
			'TradeNo'   => $trade_no,
			'Timestamp' => time(),
		);

		$parameter['MerID']       = ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? get_option( 'payuni_payment_merchant_no_test' ) : get_option( 'payuni_payment_merchant_no' );
		$parameter['Version']     = '1.0';
		$parameter['EncryptInfo'] = Payment::encrypt( $args );
		$parameter['HashInfo']    = Payment::hash_info( $parameter['EncryptInfo'] );

		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'body'    => $parameter,
		);

		$url     = ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? 'https://sandbox-api.payuni.com.tw/' : 'https://api.payuni.com.tw/';
		$request = wp_remote_request( "{$url}api/trade/cancel", $options );
		$resp    = json_decode( wp_remote_retrieve_body( $request ) );
		$data    = Payment::decrypt( $resp->EncryptInfo );

		$order = \wc_get_order( $order_id );
		if ( $order ) {
			$order->update_status( 'cancelled' );
			$order->save();
		}

		return $data;
	}

	/**
	 * Handle cancel credit bind
	 * 取消綁卡
	 *
	 * @param string $CreditHash The card hash.
	 *
	 * @return void
	 */
	public function cancel_credit_bind( string $CreditHash ): void {
		$args = array(
			'MerID'        => ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? get_option( 'payuni_payment_merchant_no_test' ) : get_option( 'payuni_payment_merchant_no' ),
			'UseTokenType' => 1,
			'BindVal'      => $CreditHash,
			'Timestamp'    => time(),
		);

		$parameter['MerID']       = ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? get_option( 'payuni_payment_merchant_no_test' ) : get_option( 'payuni_payment_merchant_no' );
		$parameter['Version']     = '1.0';
		$parameter['EncryptInfo'] = Payment::encrypt( $args );
		$parameter['HashInfo']    = Payment::hash_info( $parameter['EncryptInfo'] );

		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'body'    => $parameter,
		);

		$url     = ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? 'https://sandbox-api.payuni.com.tw/' : 'https://api.payuni.com.tw/';
		$request = wp_remote_request( $url . 'api/credit_bind/cancel', $options );
		$resp    = json_decode( wp_remote_retrieve_body( $request ) );

		ob_start();
		print_r( $resp );
		$log = ob_get_clean();

		Payment::log( $log );
	}

	/**
	 * 查詢單筆交易
	 *
	 * @see https://www.payuni.com.tw/docs/web/#/7/164
	 * @param  string $trade_no
	 *
	 * @return void
	 */
	private function get_trade_by_order( \WC_Order $order ): array {
		$trade_no = $order->get_meta( '_payuni_resp_trade_no' );
		$args     = array(
			'MerID'     => ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? get_option( 'payuni_payment_merchant_no_test' ) : get_option( 'payuni_payment_merchant_no' ),
			'TradeNo'   => $trade_no,
			'Timestamp' => time(),
		);

		$parameter['MerID']       = ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? get_option( 'payuni_payment_merchant_no_test' ) : get_option( 'payuni_payment_merchant_no' );
		$parameter['Version']     = '2.0';
		$parameter['EncryptInfo'] = Payment::encrypt( $args );
		$parameter['HashInfo']    = Payment::hash_info( $parameter['EncryptInfo'] );

		$options = array(
			'method'  => 'POST',
			'timeout' => 60,
			'body'    => $parameter,
		);

		$url     = ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? 'https://sandbox-api.payuni.com.tw/' : 'https://api.payuni.com.tw/';
		$request = wp_remote_request( "{$url}api/trade/query", $options );
		$resp    = json_decode( wp_remote_retrieve_body( $request ) );
		$data    = Payment::decrypt( $resp->EncryptInfo );

		return $data;
	}

	/**
	 * 當 order 狀態轉到 退款 時觸發
	 * CloseStatus 1=請款申請中  2=請款成功  3=請款取消  7=請款處理中  9=未申請
	 *
	 * @param  integer $order_id
	 * @param  string  $old_status
	 * @param  string  $new_status
	 *
	 * @return void
	 */
	public function handle_refund( int $order_id, string $old_status, string $new_status ): void {
		if ( 'refunded' !== $new_status ) {
			return;
		}
		$order = \wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$trade_info = $this->get_trade_by_order( $order );

		ob_start();
		print_r( $trade_info );
		$trade_info_log = ob_get_clean();
		Payment::log( $trade_info_log );

		$closeStatus = $trade_info['Result']['0']['CloseStatus'] ?? null;

		switch ( $closeStatus ) {
			case 1:
				// 如果 1=請款申請中，取消交易授權
				$res    = $this->cancel_trade_by_order( $order );
				$status = $res['Status'] ?? null;
				ob_start();
				print_r( $res );
				$note  = ob_get_clean();
				$note .= 'SUCCESS' === $status ? '<br><br>🚩 統一金流已退款成功 不需再去統一金流後台退款' : '';
				break;
			case 2:
			case 7:
				// 如果 2=請款成功 7=請款處理中，就申請退款
				$res    = $this->refund_by_order( $order );
				$status = $res['Status'] ?? null;
				ob_start();
				print_r( $res );
				$note  = ob_get_clean();
				$note .= 'SUCCESS' === $status ? '<br><br>🚩 統一金流已退款成功 不需再去統一金流後台退款' : '';
				break;
			default:
				// 都不是的話，改回舊狀態
				ob_start();
				print_r( $trade_info );
				$note = ob_get_clean();
				$order->update_status( $old_status );
				break;
		}

		$order->add_order_note( $note );

		Payment::log( $note );

		$order->save();

		return;
	}
}

Refund::init();
