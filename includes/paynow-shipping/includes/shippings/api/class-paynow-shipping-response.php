<?php
/**
 * PayNow_Shipping_Response class file.
 *
 * @package paynow
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handling PayNow shipping response callback
 */
class PayNow_Shipping_Response {

	/**
	 * Class instance.
	 *
	 * @var PayNow_Shipping_Response
	 */
	private static $instance;

	/**
	 * Initialize class and add hooks.
	 *
	 * @return void
	 */
	public static function init() {
		self::get_instance();

		// 選擇超商後，接收超商資訊.
		add_action( 'woocommerce_api_paynow_choose_cvs_callback', array( self::get_instance(), 'paynow_choose_cvs_callback' ) );

		// 物流貨態回傳.
		add_action( 'woocommerce_api_paynow_shipping_order_callback', array( self::get_instance(), 'paynow_receive_order_status_update' ) );

		// 根據貨態更新訂單狀態.
		add_action( 'paynow_update_shipping_order_status', array( self::get_instance(), 'paynow_update_order_status_after_received_update' ), 10, 2 );

	}

	/**
	 * Choose CVS callback when use return from CVS map.
	 *
	 * @return void
	 */
	public static function paynow_choose_cvs_callback() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		global $woocommerce;

		$posted   = wc_clean( wp_unslash( $_POST ) );
		$cvs_info = array();

		if ( ! empty( $posted ) ) {

			foreach ( array( 'service', 'storename', 'storeid', 'storeaddress' ) as $key ) {
				if ( isset( $posted[ $key ] ) ) {
					$cvs_info[ 'paynow_' . $key ] = $posted[ $key ];
				}
			}

			// May received additional data for CVS Family Frozen shipping.
			$cvs_info = apply_filters( 'paynow_shipping_cvs_callback', $cvs_info, $posted );
		}

		// post to checkout page, so the cvs field can be saved.
		$html  = '<!doctype html><html ' . get_language_attributes( 'html' ) . '><head><meta charset="' . get_bloginfo( 'charset', 'display' ) . '"><title>AutoSubmitForm</title></head><body>';
		$html .= '<form method="post" id="paynow-map-redirect" action="' . esc_url( wc_get_page_permalink( 'checkout' ) ) . '" style="display:none;">';
		foreach ( $cvs_info as $key => $value ) {
			$html .= '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
		}
		$html .= '</form>';
		$html .= '<script type="text/javascript">document.getElementById("paynow-map-redirect").submit();</script>';
		$html .= '</body></html>';

		echo $html;
		die();
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Handling order status update from PayNow.
	 *
	 * @return void
	 */
	public static function paynow_receive_order_status_update() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$posted = wc_clean( wp_unslash( $_POST ) );
		PayNow_Shipping::log( 'receive_order_status_update: ' . wc_print_r( $posted, true ) );

		$orderno              = $posted['orderno'];
		$original_orderno     = $posted['OriginOrderno'];
		$paynow_logistic_code = $posted['PayNowLogisticCode'];
		$detailed_status      = $posted['Detail_Status_Description'];
		$paymentno            = $posted['paymentno'];
		$store_date           = $posted['StoreDate'];
		$store_time           = $posted['StoreTime'];

		// FIXME: orderno may be prefixed.
		$order = wc_get_order( $orderno );
		if ( $order ) {
			$order->update_meta_data( '_paynow_shipping_original_order', $original_orderno );
			$order->update_meta_data( PayNow_Shipping_Order_Meta::LogisticCode, $paynow_logistic_code );
			$order->update_meta_data( PayNow_Shipping_Order_Meta::DetailStatusDesc, $detailed_status );
			$order->update_meta_data( PayNow_Shipping_Order_Meta::PaymentNo, $paymentno );
			$order->update_meta_data( PayNow_Shipping_Order_Meta::StoreDate, $store_date );
			$order->update_meta_data( PayNow_Shipping_Order_Meta::StoreTime, $store_time );
			$order->add_order_note( 'Shipping status update from PayNow: logistic_code:' . $paynow_logistic_code . ', detailed_status:' . $detailed_status . ', paymentno:' . $paymentno . ', store_date: ' . $store_date . ', store_time:' . $store_time );

			// change order status based on logistic code.
			do_action( 'paynow_update_shipping_order_status', $order, $paynow_logistic_code );
		}

		exit;
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Update order status when received update from paynow
	 *
	 * @param WC_Order $order The order object.
	 * @param string   $logistic_code PayNow logistic code.
	 * @return void
	 */
	public function paynow_update_order_status_after_received_update( $order, $logistic_code ) {

		PayNow_Shipping::log( 'Update order stauts. Order id:' . $order->get_id() . ', logistic code:' . $logistic_code );

		if ( PayNow_Shipping_Status::AT_SENDER_CVS === $logistic_code ) {
			if ( ! empty( PayNow_Shipping::$order_status_at_sender_cvs ) ) {
				$order->update_status( PayNow_Shipping::$order_status_at_sender_cvs );
			}
		} elseif ( PayNow_Shipping_Status::AT_RECEIVER_CVS === $logistic_code ) {
			if ( ! empty( PayNow_Shipping::$order_status_at_receiver_cvs ) ) {
				$order->update_status( PayNow_Shipping::$order_status_at_receiver_cvs );
			}
		} elseif ( PayNow_Shipping_Status::CUSTOMER_PICKUP === $logistic_code ) {
			if ( ! empty( PayNow_Shipping::$order_status_pickuped ) ) {
				$order->update_status( PayNow_Shipping::$order_status_pickuped );
			}
		} elseif ( PayNow_Shipping_Status::EC_RETURN === $logistic_code ) {
			if ( ! empty( PayNow_Shipping::$order_status_returned ) ) {
				$order->update_status( PayNow_Shipping::$order_status_returned );
			}
		}
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		// do nothing.
	}

	/**
	 * Initialize the class and return instance.
	 *
	 * @return PayNow_Shipping_Response
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
