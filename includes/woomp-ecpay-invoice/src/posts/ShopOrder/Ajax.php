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

		if ( ! wp_verify_nonce( $_POST['nonce'], 'invoice_handler' ) ) {
			echo __( '發生錯誤，不合法的請求來源！', 'woomp' );
			exit;
		}

		$order_id = intval( sanitize_text_field( $_POST['orderId'] ) );

		$invoice_data = [
			'_invoice_type'         => sanitize_text_field( $_POST['_invoice_type'] ),
			'_invoice_individual'   => sanitize_text_field( $_POST['_invoice_individual'] ),
			'_invoice_carrier'      => sanitize_text_field( $_POST['_invoice_carrier'] ),
			'_invoice_company_name' => sanitize_text_field( $_POST['_invoice_company_name'] ),
			'_invoice_tax_id'       => sanitize_text_field( $_POST['_invoice_tax_id'] ),
			'_invoice_donate'       => sanitize_text_field( $_POST['_invoice_donate'] ),
		];

		$order = wc_get_order( $order_id );
		$order->update_meta_data( '_ecpay_invoice_data', $invoice_data );
		$order->save();

		if ( ! empty( $order_id ) ) {
			$msg = $this->invoice_handler->generate_invoice( $order_id );
			echo $msg;
		}

		wp_die();
	}

	/**
	 * 作廢發票
	 */
	public function invalid_invoice() {

		if ( ! wp_verify_nonce( $_POST['nonce'], 'invoice_handler' ) ) {
			echo __( '發生錯誤，不合法的請求來源！', 'woomp' );
			exit;
		}

		$order_id = intval( sanitize_text_field( $_POST['orderId'] ) );

		if ( ! empty( $order_id ) ) {
			$msg = $this->invoice_handler->invalid_invoice( $order_id );
			echo $msg;
		}

		wp_die();
	}
}

Ajax::init();
