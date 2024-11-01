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
		add_action( 'wp_ajax_update_invoice', [ $class, 'update_invoice' ] );
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

		$update_result = $this->update_invoice_data();

		$order_id = (int) $_POST['orderId']; // phpcs:ignore

		if ( $update_result ) {
			$msg = $this->invoice_handler->generate_invoice( $order_id );
			\wp_send_json_success( $msg );
		} else {
			\wp_send_json_error( __( '發票資料更新失敗！', 'woomp' ) );
		}
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
			\wp_send_json_success( $msg );
		}

		\wp_die();
	}


	/**
	 * Ajax 更新發票資料
	 *
	 * @return void
	 */
	public function update_invoice(): void {

		if ( ! \wp_verify_nonce( $_POST['nonce'], 'invoice_handler' ) ) { // phpcs:ignore
			\wp_send_json_error( __( '發生錯誤，不合法的請求來源！', 'woomp' ) );
			\wp_die();
		}

		$update_result = $this->update_invoice_data();

		if ( $update_result ) {
			\wp_send_json_success( __( '發票資料更新成功！', 'woomp' ) );
		} else {
			\wp_send_json_error( __( '發票資料更新失敗！', 'woomp' ) );
		}

		\wp_die();
	}


	/**
	 * 更新發票資料
	 *
	 * @return bool 是否成功
	 */
	public function update_invoice_data(): bool {

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
			return false;
		}
		$order->update_meta_data( '_ecpay_invoice_data', $invoice_data );
		$order->save();

		return true;
	}
}

Ajax::init();
