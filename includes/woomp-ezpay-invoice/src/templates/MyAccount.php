<?php

namespace WOOMPEZPAYINVOICE\Templates;

defined( 'ABSPATH' ) || exit;


class MyAccount {
	public static function register() {
		$class = new self();
		add_filter( 'woocommerce_account_orders_columns', [ $class, 'add_account_orders_column' ] );
		add_action( 'woocommerce_my_account_my_orders_column_invoice-number', [ $class, 'add_account_orders_column_rows' ] );
	}
	public function __construct() {
	}

	/**
	 * Add_account_orders_column
	 */
	public function add_account_orders_column( $columns ) {
		$add_index = array_search( 'order-total', array_keys( $columns ) ) + 1;
		$pre_array = array_splice( $columns, 0, $add_index );
		$array     = [
			'invoice-number' => __( 'Invoice number', 'woomp' ),
		];
		return array_merge( $pre_array, $array, $columns );
	}

	/**
	 * Add_account_orders_column_rows
	 */
	public function add_account_orders_column_rows( $order ) {
		if ( $value = $order->get_meta( '_ezpay_invoice_number' ) ) {
			echo esc_html( $value );
		}
	}
}

MyAccount::register();
