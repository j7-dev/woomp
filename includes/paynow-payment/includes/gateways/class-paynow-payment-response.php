<?php
/**
 * PayNow_Payment_Response class file
 *
 * @package paynow
 */

defined( 'ABSPATH' ) || exit;

/**
 * Receive response from PayNow.
 */
class PayNow_Payment_Response {

	/**
	 * Class instance
	 *
	 * @var PayNow_Payment_Response
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
	 * @return PayNow_Payment_Response
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
		self::get_instance();

		// Payment listener/API hook, 此網址請設定在 PayNow 後台.
		add_action( 'woocommerce_api_paynow_payment', array( self::get_instance(), 'paynow_receive_response' ) );
		add_action( 'paynow_payment_online_response', array( self::get_instance(), 'paynow_valid_response' ) );
		add_action( 'paynow_payment_online_background_notification', array( self::get_instance(), 'paynow_background_notification' ) );

		// offline Payment listener/API hook, 此網址請設定在 PayNow 後台.
		add_action( 'woocommerce_api_paynow_payment_offline', array( self::get_instance(), 'paynow_receive_response' ) );
		add_action( 'paynow_payment_offline_response', array( self::get_instance(), 'paynow_valid_offline_response' ) );

	}

	/**
	 * Receive response from PayNow
	 *
	 * @return void
	 */
	public static function paynow_receive_response() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		global $woocommerce;

		PayNow_Payment::log( 'paynow_receive_response. raw post data ' . wc_print_r( $_POST, true ) );

		if ( ! empty( $_POST ) && self::validate_passcode() ) {

			$posted = wc_clean( wp_unslash( $_POST ) );

			if ( current_action() === 'woocommerce_api_paynow_payment' ) {
				if ( self::is_background_notification( $posted ) ) {
					do_action( 'paynow_payment_online_background_notification', $posted );
				} else {
					do_action( 'paynow_payment_online_response', $posted );
				}

			} elseif ( current_action() === 'woocommerce_api_paynow_payment_offline' ) {
				if ( self::is_background_notification( $posted ) ) {
					do_action( 'paynow_payment_online_background_notification', $posted );
				} else {
					do_action( 'paynow_payment_offline_response', $posted );
				}
			}

			exit;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

	}

	// 信用卡(01)、WebATM(02) 即時通知結果
	/**
	 * Update post meta using the received post data
	 *
	 * @param array $posted The post data received from PayNow.
	 * @return void
	 */
	public static function paynow_valid_response( $posted ) {
		global $woocommerce;

		if ( self::is_background_notification( $posted ) ) {
			return;
		}

		$web_no      = $posted['WebNo'];
		$buysafe_no  = $posted['BuysafeNo']; // PayNow訂單編號.
		$pass_code   = $posted['PassCode'];
		$order_no    = $posted['OrderNo'];
		$tran_status = $posted['TranStatus'];// 交易結果 S or F.
		$pay_type    = $posted['PayType'];
		$pan_no4     = $posted['pan_no4'];// 信用卡末四碼.

		PayNow_Payment::log( 'Order ' . $order_no . ' response received from PayNow. ' . wc_print_r( $posted, true ) );

		$site_web_no = get_option( 'paynow_payment_web_no' );
		if ( $web_no !==  $site_web_no ) {
			PayNow_Payment::log( 'Received WebNo '. $web_no . ' is not the same as the site WebNo ' . $site_web_no . ' do nothing' );
			return;
		}

		$order = self::get_paynow_order( $posted );

		if ( $order ) {

			update_post_meta( $order->get_id(), '_paynow_tran_id', $buysafe_no );
			update_post_meta( $order->get_id(), '_paynow_tran_status', $tran_status );

			// 01:信用卡, 09:銀聯, 11:分期付款.
			if ( PayNow_Pay_Type::CREDIT === $pay_type || PayNow_Pay_Type::UNION === $pay_type || PayNow_Pay_Type::CREDIT_INSTALL === $pay_type ) {
				update_post_meta( $order->get_id(), '_paynow_pan_no4', $pan_no4 );
			}

			// 定期定額.
			if ( array_key_exists( 'CIFID', $posted ) ) {
				update_post_meta( $order->get_id(), '_cifid', $posted['CIFID'] );
				update_post_meta( $order->get_id(), '_cifpw', $posted['CIFPW'] );
				update_post_meta( $order->get_id(), '_cifid_sn', $posted['CIFID_SN'] );
			}

			if ( 'S' === $tran_status ) {

				$order->payment_complete( $buysafe_no );
				$woocommerce->cart->empty_cart();
				$order->update_status( 'processing' );

			} else {

				$err_desc = $posted['ErrDesc'];

				$order->update_status( 'on-hold' );
				$woocommerce->cart->empty_cart();

				update_post_meta( $order->get_id(), '_paynow_errdesc', urldecode( $err_desc ) );

			}

			wp_safe_redirect( $order->get_checkout_order_received_url() );
			exit;
		}
	}

	/**
	 * Receive offline payment (虛擬帳號、超商代碼、條碼) response from PayNow
	 *
	 * @param array $posted The post data received from PayNow.
	 * @return void
	 */
	public static function paynow_valid_offline_response( $posted ) {
		global $woocommerce;

		if ( self::is_background_notification( $posted ) ) {
			return;
		}

		$web_no     = $posted['WebNo'];
		$buysafe_no = $posted['BuysafeNo'];// paynow訂單編號.
		$pass_code  = $posted['PassCode'];
		$order_no   = $posted['OrderNo'];
		$pay_type   = $posted['PayType'];

		$new_date = urldecode( $posted['NewDate'] );
		$due_date = urldecode( $posted['DueDate'] );

		$tran_status = $posted['TranStatus'];

		$order = self::get_paynow_order( $posted );
		PayNow_Payment::log( 'Order ' . $order_no . ' offline payment response received from PayNow. ' . wc_print_r( $posted, true ) );

		$site_web_no = get_option( 'paynow_payment_web_no' );
		if ( $web_no !==  $site_web_no ) {
			PayNow_Payment::log( 'Received WebNo '. $web_no . ' is not the same as the site WebNo ' . $site_web_no . ' do nothing' );
			return;
		}

		if ( $order ) {

			update_post_meta( $order->get_id(), '_paynow_new_date', $new_date );
			update_post_meta( $order->get_id(), '_paynow_due_date', $due_date );

			update_post_meta( $order->get_id(), '_paynow_tran_id', $buysafe_no );
			update_post_meta( $order->get_id(), '_paynow_tran_status', $tran_status );
			update_post_meta( $order->get_id(), '_transaction_id', $buysafe_no );

			// 虛擬帳號 virtual account pay type = 03.
			if ( PayNow_Pay_Type::VIRTUAL_ACCOUNT === $pay_type ) {

				$bank_code = $posted['BankCode'];
				$atm_no    = $posted['ATMNo'];
				update_post_meta( $order->get_id(), '_paynow_bank_code', $bank_code );
				update_post_meta( $order->get_id(), '_paynow_atm_no', $atm_no );

			}

			// 條碼 barcode pay type=10.
			if ( PayNow_Pay_Type::BARCODE === $pay_type ) {

				$barcode1 = $posted['BarCode1'];
				$barcode2 = $posted['BarCode2'];
				$barcode3 = $posted['BarCode3'];
				update_post_meta( $order->get_id(), 'barcode1', $barcode1 );
				update_post_meta( $order->get_id(), 'barcode2', $barcode2 );
				update_post_meta( $order->get_id(), 'barcode3', $barcode3 );

			}

			// ibon 超商代碼(ibon) pay type = 05.
			if ( PayNow_Pay_Type::IBON === $pay_type ) {

				$ibon_no   = $posted['IBONNO'];
				$passcode2 = $posted['PassCode2'];
				update_post_meta( $order->get_id(), '_paynow_ibon_no', $ibon_no );
				// TODO: should validate passcode2.
				update_post_meta( $order->get_id(), '_paynow_passcode2', $passcode2 );

			}

			if ( 'S' === $tran_status ) {

				$order->add_order_note( __( 'Recieved PayNow payment completed notification', 'paynow-payment' ) );
				$order->payment_complete( $buysafe_no );
				$woocommerce->cart->empty_cart();

			} else {

				$order->update_status( 'on-hold' );
				$woocommerce->cart->empty_cart();

			}

			wp_safe_redirect( $order->get_checkout_order_received_url() );
			exit;
		}
	}

	//成功才會回傳
	public static function paynow_background_notification( $posted ) {
		global $woocommerce;

		$web_no      = $posted['WebNo'];
		$buysafe_no  = $posted['BuysafeNo']; // PayNow訂單編號.
		$pass_code   = $posted['PassCode'];
		$order_no    = $posted['OrderNo'];
		$tran_status = $posted['TranStatus'];// 交易結果 S or F.
		$pay_type    = $posted['PayType'];//交易類別
		$pan_no4     = $posted['Pan_no4'];// 信用卡末四碼.

		PayNow_Payment::log( 'Order ' . $order_no . ' background notification received from PayNow. ' . wc_print_r( $posted, true ) );

		$site_web_no = get_option( 'paynow_payment_web_no' );
		if ( $web_no !==  $site_web_no ) {
			PayNow_Payment::log( 'Received WebNo '. $web_no . ' is not the same as the site WebNo ' . $site_web_no . ' do nothing' );
			return;
		}

		$order = self::get_paynow_order( $posted );
		if ( $order ) {
			update_post_meta( $order->get_id(), '_paynow_tran_id', $buysafe_no );
			update_post_meta( $order->get_id(), '_paynow_tran_status', $tran_status );
			if ( ! empty( $pan_no4 ) ) {
				update_post_meta( $order->get_id(), '_paynow_pan_no4', $pan_no4 );
			}

			if ( !$order->is_paid() && 'S' === $tran_status ) {
				$order->payment_complete( $buysafe_no );
			}

			$woocommerce->cart->empty_cart();
			echo '1';
		}

	}

	private static function is_background_notification( $posted ) {
		$method = ( array_key_exists( 'method', $posted ) )? $posted['method']: '';
		if ( 'paynow_return' === $method ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Validate passcode
	 *
	 * @return void
	 */
	private static function validate_passcode() {
		// TODO: need validate passcode.
		$posted   = wc_clean( wp_unslash( $_POST ) );
		$passcode = $posted['PassCode'];
		return true;
	}

	/**
	 * Get PayNow order from posted data.
	 *
	 * @param array $posted The posted data.
	 * @return WC_Order
	 */
	private static function get_paynow_order( $posted ) {
		return wc_get_order( $posted['OrderNo'] );
	}
}
