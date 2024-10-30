<?php

namespace WOOMPECPAYINVOICE\ShopOrder;

use WOOMPECPAYINVOICE\APIs\EcpayInvoiceHandler;

defined( 'ABSPATH' ) || exit;

class Ajax {


	private $invoice_handler;

	public static function init() {
		$class = new self();
		add_action( 'wp_ajax_gen_invoice', [ $class, 'generate_invoice' ] );
		add_action( 'wp_ajax_invalid_invoice', [ $class, 'invalid_invoice' ] );
	}

	public function __construct() {
		$this->invoice_handler = new EcpayInvoiceHandler();
	}

	/**
	 * 開立發票
	 */
	public function generate_invoice() {

		if ( ! \wp_verify_nonce( $_POST['nonce'], 'invoice_handler' ) ) { // phpcs:ignore
			\wp_send_json_error( __( '發生錯誤，不合法的請求來源！', 'woomp' ) );
			\wp_die();
		}

		$order_id = (int) $_POST['orderId']; // phpcs:ignore

		$invoice_data_keys = [
			'_invoice_type',
			'_invoice_individual',
			'_invoice_carrier',
			'_invoice_company_name',
			'_invoice_tax_id',
			'_invoice_donate',
		];

		$invoice_data = [];
		foreach ( $invoice_data_keys as $key ) {
			$invoice_data[ $key ] = \sanitize_text_field( $_POST[ $key ] ); // phpcs:ignore
		}

		$order = \wc_get_order( $order_id );
		if ( ! $order ) {
			\wp_send_json_error( __( '訂單不存在！', 'woomp' ) );
			\wp_die();
		}
		$order->update_meta_data( '_ecpay_invoice_data', $invoice_data );
		$order->save();

		$msg = $this->invoice_handler->generate_invoice( $order_id );

		\wp_send_json_success( $msg );

		\wp_die();
	}

	/**
	 * 作廢發票
	 */
	public function invalid_invoice() {

		if ( ! wp_verify_nonce( $_POST['nonce'], 'invoice_handler' ) ) { // phpcs:ignore
			\wp_send_json_error( __( '發生錯誤，不合法的請求來源！', 'woomp' ) );
		}

		$order_id = (int) $_POST['orderId']; // phpcs:ignore

		if ( ! empty( $order_id ) ) {
			$msg = $this->invoice_handler->invalid_invoice( $order_id );
			\wp_send_json_error( $msg );
		}

		\wp_die();
	}
}

Ajax::init();
