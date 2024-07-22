<?php
/**
 * PayNow_Payment_Order_Meta_Boxes class file
 *
 * @package paynow
 */

defined( 'ABSPATH' ) || exit;

/**
 * PayNow_Payment main class for handling all checkout related process.
 */
class PayNow_Payment_WebATM extends PayNow_Abstract_Payment_Gateway {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->plugin_name = 'paynow-payment-webatm';
		$this->version     = '1.0.0';

		$this->id                 = 'paynow-webatm';
		$this->method_title       = __( 'PayNow 網頁ATM', 'paynow-payment' );
		$this->method_description = 'PayNow WebATM，前往網頁櫃員機，線上轉帳繳費';

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );

		$this->pay_type         = PayNow_Pay_Type::WEBATM;
		$this->order_result_url = add_query_arg( 'wc-api', 'paynow_payment_webatm', home_url( '/' ) );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
	}

	/**
	 * Setup form fields for payment
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = include PAYNOW_PLUGIN_DIR . 'includes/settings/settings-paynow-payment-webatm.php';
	}

	/**
	 * Process payment
	 *
	 * @param string $order_id The order id.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		global $woocommerce;
		$order = new WC_Order( $order_id );

		// Return thankyou redirect.
		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);
	}

	/**
	 * Redirect to paynow payment page
	 *
	 * @param WC_Order $order The order object.
	 * @return void
	 */
	public function receipt_page( $order ) {
		$request = new PayNow_Payment_Request( $this );
		$request->build_request_form( $order );
	}

	/**
	 * Order meta fields for payment
	 *
	 * @return array
	 */
	public static function order_metas() {
		return array(
			'_paynow_tran_id'    => __( 'Transaction No', 'paynow-payment' ),
			'_paynow_bank_code'  => __( 'Bank Code', 'paynow-payment' ),
			'_paynow_atm_no'     => __( 'ATM No', 'paynow-payment' ),
			'_paynow_new_date'   => __( 'New Date', 'paynow-payment' ),
			'_paynow_due_date'   => __( 'Due Date', 'paynow-payment' ),
			'_paynow_pay_status' => __( 'Pay Status', 'paynow-payment' ),
		);
	}

	/**
	 * Display payment detail after order table
	 *
	 * @param WC_Order $order The order object.
	 * @return void
	 */
	public function paynow_payment_detail_after_order_table( $order ) {

		if ( $order->get_payment_method() === $this->id ) {

			echo '<h2>' . esc_html( __( 'PayNow Payment Detail', 'paynow-payment' ) ) . '</h2><table class="shop_table paynow_payment_details"><tbody>';

			echo '<tr><td><strong>' . esc_html( __( 'Transaction No', 'paynow-payment' ) ) . '</strong></td>';
			echo '<td>' . esc_html( get_post_meta( $order->get_id(), '_paynow_tran_id', true ) ) . '</td></tr>';

			echo '<tr><td><strong>' . esc_html( __( 'Bank Code', 'paynow-payment' ) ) . '</strong></td>';
			echo '<td>' . esc_html( get_post_meta( $order->get_id(), '_paynow_bank_code', true ) ) . '</td></tr>';

			echo '<tr><td><strong>' . esc_html( __( 'ATM No', 'paynow-payment' ) ) . '</strong></td>';
			echo '<td>' . esc_html( get_post_meta( $order->get_id(), '_paynow_atm_no', true ) ) . '</td></tr>';

			echo '<tr><td><strong>' . esc_html( __( 'New Date', 'paynow-payment' ) ) . '</strong></td>';
			echo '<td>' . esc_html( get_post_meta( $order->get_id(), '_paynow_new_date', true ) ) . '</td></tr>';

			echo '<tr><td><strong>' . esc_html( __( 'Due Date', 'paynow-payment' ) ) . '</strong></td>';
			echo '<td>' . esc_html( get_post_meta( $order->get_id(), '_paynow_due_date', true ) ) . '</td></tr>';

			echo '<tr><td><strong>' . esc_html( __( 'Pay Status', 'paynow-payment' ) ) . '</strong></td>';
			echo '<td>' . esc_html( get_post_meta( $order->get_id(), '_paynow_pay_status', true ) ) . '</td></tr>';

			$tran_status = get_post_meta( $order->get_id(), '_paynow_tran_status', true );
			if ( 'F' === $tran_status ) {
				echo '<tr><td><strong>' . esc_html( __( 'Payment Error Description', 'paynow-payment' ) ) . '</strong></td>';
				echo '<td>' . esc_html( get_post_meta( $order->get_id(), '_paynow_errdes', true ) ) . '</td></tr>';
			}

			echo '</tbody></table>';
		}
	}
}
