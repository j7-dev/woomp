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
	public static function init( $resp ) {
		$class = self::get_instance();

		// Payment listener/API hook, 此網址請設定在 Payuni 後台.
		// add_action( 'woocommerce_api_payuni_payment', array( self::get_instance(), 'payuni_receive_response' ) );
		// add_action( 'payuni_payment_online_response', array( self::get_instance(), 'valid_response' ) );

		// offline Payment listener/API hook, 此網址請設定在 Payuni 後台.
		// add_action( 'woocommerce_api_payuni_payment_offline', array( self::get_instance(), 'payuni_receive_response' ) );
		// add_action( 'Payuni_payment_offline_response', array( self::get_instance(), 'valid_response_offline' ) );

		if ( $resp ) {
			$class->payuni_response( $resp );
		}

	}

	/**
	 * Receive response from Payuni
	 *
	 * @return void
	 */
	public function payuni_response( $resp ) {

		global $woocommerce;

		$encrypt_info = $resp->EncryptInfo;
		$hash_info    = $resp->HashInfo;
		$data         = \Payuni\APIs\Payment::decrypt( $encrypt_info );
		$order        = wc_get_order( $data['MerTradeNo'] );

		\PAYUNI\APIs\Payment::log( $data );

		$status   = $data['Status'];
		$message  = $data['Message'];
		$trade_no = $data['TradeNo'];

		$card_bank      = $data['CardBank'];
		$card_bank_name = $data['AuthBankName'];
		$card_4no       = $data['Card4No'];
		$card_hash      = $data['CreditHash'];

		$card_inst = $data['CardInst']; // 分期資料：分期數
		$first_amt = $data['FirstAmt']; // 分期資料：首期金額
		$each_amt  = $data['EachAmt']; // 分期資料：每期金額

		$order->update_meta_data( '_payuni_resp_status', $status );
		$order->update_meta_data( '_payuni_resp_message', $message );
		$order->update_meta_data( '_payuni_resp_trande_no', $trade_no );
		$order->update_meta_data( '_payuni_resp_card_bank', "({$card_bank}){$card_bank_name}" );
		$order->update_meta_data( '_payuni_card_number', $card_4no );
		$order->update_meta_data( '_payuni_resp_card_inst', $card_inst );
		$order->update_meta_data( '_payuni_resp_first_amt', $first_amt );
		$order->update_meta_data( '_payuni_resp_each_amt', $each_amt );

		$order->add_order_note( "<strong>統一金流交易紀錄<strong><br>狀態碼：{$status}<br>交易訊息：{$message}<br>交易編號：{$trade_no}<br>卡號末四碼：{$card_4no}", true );

		if ( 'SUCCESS' === $status ) {
			$user_id  = $order->get_customer_id();
			$method   = $order->get_meta( '_payment_method' );
			$remember = $order->get_meta( '_' . $method . '-card_remember' );

			if ( $user_id && $remember ) {
				update_user_meta( $user_id, '_payuni_card_4no', $card_4no );
				update_user_meta( $user_id, '_payuni_card_hash', $card_hash );
			}
			$order->update_status( 'processing' );
		} else {
			$order->update_status( 'failed' );
		}

		$woocommerce->cart->empty_cart();
		$order->save();

		// $trade_status = $data['TradeStatus']; // 付款狀態 1=已付款 2=付款失敗 3=付款取消

		// $AuthBank     = $data['AuthBank'];
		// $AuthBankName = $data['AuthBankName'];
		// $AuthType     = $data['AuthType']; // 授權類型 1=一次 2=分期 3=紅利 7=銀聯
		// $AuthDay      = $data['AuthDay'];
		// $AuthTime     = $data['AuthTime'];
		// $CreditLife   = $data['CreditLife']; // Token 期限
	}

	// 信用卡即時通知結果
	/**
	 * Update post meta using the received post data
	 *
	 * @param array $posted The post data received from Payuni.
	 * @return void
	 */
	public static function valid_response( $posted ) {

		global $woocommerce;

		\PAYUNI\APIs\Payment::log( $posted );

		$orgno           = $posted['orgno'];
		$secondtimestamp = $posted['secondtimestamp'];
		$nonce_str       = $posted['nonce_str'];
		$sign            = $posted['sign'];
		$out_trade_no    = $posted['out_trade_no'];
		$status          = $posted['status'];
		$result          = $posted['result'];
		$total_fee       = $posted['total_fee'];

		Payuni_Payment::log( 'Order ' . $result . ' response received from Payuni. ' . wc_print_r( $posted, true ) );
		$order = self::get_Payuni_order( $posted );

		if ( $order ) {
			if ( '0000' === $status ) {
				$order->payment_complete();
				$order->reduce_order_stock();
				if ( '交易成功' === $result && 'completed' !== $order->get_status() ) {
					$order->update_status( 'completed' );
				} elseif ( '核准' === $result && 'completed' !== $order->get_status() ) {
					$order->update_status( 'completed' );
				}
			} else {
				$order->update_status( 'failed' );
				$woocommerce->cart->empty_cart();
			}
			$woocommerce->cart->empty_cart();
			update_post_meta( $order->get_id(), 'payment_method', 'Payuni-credit' );
			update_post_meta( $order->get_id(), '_Payuni_result', $result );
			update_post_meta( $order->get_id(), '_Payuni_status', $status );
			$order->add_order_note( '快點付交易結果：' . $result, true );
			wp_safe_redirect( $order->get_checkout_order_received_url() );
			exit;
		}
	}

	/**
	 * Receive offline payment (虛擬帳號、超商代碼、條碼) response from Payuni
	 *
	 * @param array $posted The post data received from Payuni.
	 * @return void
	 */
	public static function valid_response_offline( $posted ) {

		$status = $posted['status'];
		$result = $posted['result'];

		Payuni_Payment::log( 'Order ' . $result . ' 離線付款觸發' . wc_print_r( $posted, true ) );
		$order = self::get_Payuni_order( $posted );

		if ( $order ) {
			if ( '0000' === $status ) {
				$order->payment_complete();
				$order->reduce_order_stock();
				update_post_meta( $order->get_id(), '_Payuni_result', $result );
				$order->add_order_note( '快點付交易結果：' . $result, true );
				$order->update_status( 'completed' );
			} else {
				$order->update_status( 'on-hold' );
				update_post_meta( $order->get_id(), '_Payuni_result', $result );
				$order->add_order_note( '快點付交易結果：' . $result, true );
			}
			exit;
		}

		wp_send_json( 'success' );
	}

	/**
	 * Validate passcode
	 *
	 * @return void
	 */
	private static function validate_passcode() {
		// TODO: need validate passcode.
		$posted   = wc_clean( wp_unslash( $_REQUEST ) );
		$passcode = $posted['sign'];
		return true;
	}

	/**
	 * Get Payuni order from posted data.
	 *
	 * @param array $posted The posted data.
	 * @return WC_Order
	 */
	private static function get_Payuni_order( $posted ) {
		return wc_get_order( $posted['out_trade_no'] );
	}
}
