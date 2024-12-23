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
		\add_action( 'payuni_cancel_trade_by_order', [ $class, 'cancel_trade_by_order' ] );
		\add_action( 'payuni_cancel_trade_by_trade_no', [ $class, 'cancel_trade_by_trade_no' ], 10, 2 );
		\add_action( 'woocommerce_order_status_changed', [ $class, 'handle_refund' ], 10, 3 );
	}

	/**
	 * Handle refund
	 * User can trigger refund in Front-End My Account page
	 */
	public function refund_by_order( \WC_Order $order ): array {
		$trade_no = $order->get_meta( '_payuni_resp_trade_no' );
		$amount   = (int) $order->get_total();

		$args = [
			'MerID'     => ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? get_option( 'payuni_payment_merchant_no_test' ) : get_option( 'payuni_payment_merchant_no' ),
			'TradeNo'   => $trade_no,
			'TradeAmt'  => $amount,
			'Timestamp' => time(),
			'CloseType' => 2,
		];

		$parameter['MerID']       = ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? get_option( 'payuni_payment_merchant_no_test' ) : get_option( 'payuni_payment_merchant_no' );
		$parameter['Version']     = '1.0';
		$parameter['EncryptInfo'] = Payment::encrypt( $args );
		$parameter['HashInfo']    = Payment::hash_info( $parameter['EncryptInfo'] );

		$options = [
			'method'     => 'POST',
			'timeout'    => 60,
			'body'       => $parameter,
			'user-agent' => 'payuni',
		];

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
		$args     = [
			'MerID'     => ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? get_option( 'payuni_payment_merchant_no_test' ) : get_option( 'payuni_payment_merchant_no' ),
			'TradeNo'   => $trade_no,
			'Timestamp' => time(),
		];

		$parameter['MerID']       = ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? get_option( 'payuni_payment_merchant_no_test' ) : get_option( 'payuni_payment_merchant_no' );
		$parameter['Version']     = '1.0';
		$parameter['EncryptInfo'] = Payment::encrypt( $args );
		$parameter['HashInfo']    = Payment::hash_info( $parameter['EncryptInfo'] );

		$options = [
			'method'     => 'POST',
			'timeout'    => 60,
			'body'       => $parameter,
			'user-agent' => 'payuni',
		];

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
		$args = [
			'MerID'     => ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? get_option( 'payuni_payment_merchant_no_test' ) : get_option( 'payuni_payment_merchant_no' ),
			'TradeNo'   => $trade_no,
			'Timestamp' => time(),
		];

		$parameter['MerID']       = ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? get_option( 'payuni_payment_merchant_no_test' ) : get_option( 'payuni_payment_merchant_no' );
		$parameter['Version']     = '1.0';
		$parameter['EncryptInfo'] = Payment::encrypt( $args );
		$parameter['HashInfo']    = Payment::hash_info( $parameter['EncryptInfo'] );

		$options = [
			'method'     => 'POST',
			'timeout'    => 60,
			'body'       => $parameter,
			'user-agent' => 'payuni',
		];

		$url     = ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? 'https://sandbox-api.payuni.com.tw/' : 'https://api.payuni.com.tw/';
		$request = wp_remote_request( "{$url}api/trade/cancel", $options );
		$resp    = json_decode( wp_remote_retrieve_body( $request ) );
		$data    = Payment::decrypt( $resp->EncryptInfo );

		$order       = \wc_get_order( $order_id );
		$no_checkout = $order->get_meta( 'no_checkout' ) === 'yes';
		if ( $order && $no_checkout ) {
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
		$args = [
			'MerID'        => ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? get_option( 'payuni_payment_merchant_no_test' ) : get_option( 'payuni_payment_merchant_no' ),
			'UseTokenType' => 1,
			'BindVal'      => $CreditHash,
			'Timestamp'    => time(),
		];

		$parameter['MerID']       = ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? get_option( 'payuni_payment_merchant_no_test' ) : get_option( 'payuni_payment_merchant_no' );
		$parameter['Version']     = '1.0';
		$parameter['EncryptInfo'] = Payment::encrypt( $args );
		$parameter['HashInfo']    = Payment::hash_info( $parameter['EncryptInfo'] );

		$options = [
			'method'     => 'POST',
			'timeout'    => 60,
			'body'       => $parameter,
			'user-agent' => 'payuni',
		];

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
	 * @return array
	 */
	private function get_trade_by_order( \WC_Order $order ): array {
		$trade_no = $order->get_meta( '_payuni_resp_trade_no' );
		$args     = [
			'MerID'     => ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? get_option( 'payuni_payment_merchant_no_test' ) : get_option( 'payuni_payment_merchant_no' ),
			'TradeNo'   => $trade_no,
			'Timestamp' => time(),
		];

		$parameter['MerID']       = ( wc_string_to_bool( get_option( 'payuni_payment_testmode' ) ) ) ? get_option( 'payuni_payment_merchant_no_test' ) : get_option( 'payuni_payment_merchant_no' );
		$parameter['Version']     = '2.0';
		$parameter['EncryptInfo'] = Payment::encrypt( $args );
		$parameter['HashInfo']    = Payment::hash_info( $parameter['EncryptInfo'] );

		$options = [
			'method'     => 'POST',
			'timeout'    => 60,
			'body'       => $parameter,
			'user-agent' => 'payuni',
		];

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
	 * @see https://www.payuni.com.tw/docs/web/#/7/164
	 *
	 * @param  integer $order_id 訂單 ID
	 * @param  string  $old_status 舊狀態
	 * @param  string  $new_status 新狀態
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
		$payment_method = $order->get_payment_method();
		if ( ! str_starts_with( $payment_method, 'payuni-' ) ) {
			return;
		}

		$trade_info = $this->get_trade_by_order( $order );

		ob_start();
		print_r( $trade_info );
		$note = ob_get_clean();
		Payment::log( $note );

		$close_status = $trade_info['Result']['0']['CloseStatus'] ?? null;

		switch ( $close_status ) {
			case 1:
				// 如果 1=請款申請中，取消交易授權
				$res    = $this->cancel_trade_by_order( $order );
				$status = $res['Status'] ?? null;
				ob_start();
				print_r( $res );
				$note  = ob_get_clean();
				$note .= 'SUCCESS' === $status ? '<br><br>🚩 統一金流已退款成功 不需再去統一金流後台退款' : '<br><br>🚩 統一金流退款失敗，請至統一金流後台手動退款';
				break;
			case 3:
				$note = '<br><br>🚩 此筆訂單在統一金流後台狀態為【3=請款取消】，本來就不會請款';
				break;
			case 9:
				$note = '<br><br>🚩 此筆訂單在統一金流後台狀態為【9=未申請】';
				break;
			default:
				// 如果 2=請款成功 7=請款處理中，就申請退款 3=請款取消
				$res    = $this->refund_by_order( $order );
				$status = $res['Status'] ?? null;
				ob_start();
				print_r( $res );
				$note  = ob_get_clean();
				$note .= 'SUCCESS' === $status ? '<br><br>🚩 統一金流已退款成功 不需再去統一金流後台退款' : '<br><br>🚩 統一金流退款失敗，請至統一金流後台手動退款';
				break;
		}

		$order->add_order_note( $note );

		Payment::log( $note );

		$order->save();

		return;
	}
}

Refund::init();
