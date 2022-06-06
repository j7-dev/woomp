<?php

namespace WOOMPECPAYINVOICE\ShopOrder;

use PostTypes\PostType;
use WOOMPECPAYINVOICE\APIs\EcpayInvoiceHandler;

defined( 'ABSPATH' ) || exit;

class Admin {

	public static function init() {
		$class = new self();
		add_action( 'admin_enqueue_scripts', array( $class, 'enqueue_script' ) );
		add_filter( 'manage_shop_order_posts_columns', array( $class, 'shop_order_columns' ), 11, 1 );
		add_action( 'manage_shop_order_posts_custom_column', array( $class, 'shop_order_column' ), 11, 2 );
		add_action( 'save_post', array( $class, 'update_invoice_data' ), 10, 3 );
		add_action( 'admin_init', array( $class, 'update_invoice_exist_data' ), 20 );

		if ( 'auto' === get_option( 'wc_woomp_ecpay_invoice_issue_mode' ) ) {
			$invoice_issue_at = str_replace( 'wc-', '', get_option( 'wc_woomp_ecpay_invoice_issue_at' ) );
			add_action( 'woocommerce_order_status_' . $invoice_issue_at, array( $class, 'issue_invoice' ), 10, 1 );
		}

		if ( 'auto' === get_option( 'wc_woomp_ecpay_invoice_invalid_mode' ) ) {
			$invoice_invalid_at = str_replace( 'wc-', '', get_option( 'wc_woomp_ecpay_invoice_invalid_at' ) );
			add_action( 'woocommerce_order_status_' . $invoice_invalid_at, array( $class, 'invalid_invoice' ), 10, 1 );
		}
	}

	public function enqueue_script() {
		wp_register_script( 'woomp_ecpay_invoice', ECPAYINVOICE_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), '1.0.2', true );
		wp_localize_script(
			'woomp_ecpay_invoice',
			'woomp_ecpay_invoice_params',
			array(
				'ajax_nonce' => wp_create_nonce( 'invoice_handler' ),
				'post_id'    => get_the_ID(),
			)
		);
		wp_enqueue_script( 'woomp_ecpay_invoice' );
	}

	/**
	 * 後台訂單列表增加單號欄位
	 */
	public function shop_order_columns( $columns ) {
		$add_index = array_search( 'shipping_address', array_keys( $columns ) ) + 1;
		$pre_array = array_splice( $columns, 0, $add_index );
		$array     = array(
			'wmp_invoice_no' => __( 'Invoice number', 'woomp' ),
		);
		return array_merge( $pre_array, $array, $columns );
	}

	public function shop_order_column( $column, $post_id ) {
		$order = wc_get_order( $post_id );
		if ( 'wmp_invoice_no' === $column && '0' !== $order->get_total() ) {
			if ( get_post_meta( $post_id, '_ecpay_invoice_number', true ) ) {
				echo get_post_meta( $post_id, '_ecpay_invoice_number', true );
				echo '<br><button type="button" class="button btnInvalidInvoice" value="' . $post_id . '">' . __( 'Invalid invoice', 'woomp' ) . '</button>';
			} else {
				echo '<button type="button" class="button btnGenerateInvoice" value="' . $post_id . '">' . __( 'Generate invoice', 'woomp' ) . '</button>';
			}
		}
	}

	/**
	 * 更新電子發票資訊
	 */
	public function update_invoice_data( $post_id, $post, $update ) {

		global $pagenow;
		if ( get_option( 'wc_woomp_enabled_ecpay_invoice' ) && 'post.php' === $pagenow && 'shop_order' === get_post_type( $_GET['post'] ) ) {

			$order        = wc_get_order( $post_id );
			$invoice_data = array();

			if ( isset( $_POST['_invoice_type'] ) ) {
				$invoice_data['_invoice_type'] = wp_unslash( $_POST['_invoice_type'] );
			}

			if ( isset( $_POST['_invoice_individual'] ) ) {
				$invoice_data['_invoice_individual'] = wp_unslash( $_POST['_invoice_individual'] );
			} else {
				$invoice_data['_invoice_individual'] = false;
			}

			if ( isset( $_POST['_invoice_carrier'] ) && ! empty( $_POST['_invoice_carrier'] ) ) {
				$invoice_data['_invoice_carrier'] = wp_unslash( $_POST['_invoice_carrier'] );
			}

			if ( isset( $_POST['_invoice_company_name'] ) && ! empty( $_POST['_invoice_company_name'] ) ) {
				$invoice_data['_invoice_company_name'] = wp_unslash( $_POST['_invoice_company_name'] );
			}

			if ( isset( $_POST['_invoice_tax_id'] ) && ! empty( $_POST['_invoice_tax_id'] ) ) {
				$invoice_data['_invoice_tax_id'] = wp_unslash( $_POST['_invoice_tax_id'] );
			}

			if ( isset( $_POST['_invoice_donate'] ) && ! empty( $_POST['_invoice_donate'] ) ) {
				$invoice_data['_invoice_donate'] = wp_unslash( $_POST['_invoice_donate'] );
			}

			if ( count( $invoice_data ) > 0 ) {
				$order->update_meta_data( '_ecpay_invoice_data', $invoice_data );
				$order->save();
			}
		}
	}

	/**
	 * 讀取既有的訂單電子發票資訊
	 */
	public function update_invoice_exist_data() {
		global $pagenow;
		if ( get_option( 'wc_woomp_enabled_ecpay_invoice' ) && 'post.php' === $pagenow && 'shop_order' === get_post_type( $_GET['post'] ) ) {

			$order        = wc_get_order( $_GET['post'] );
			$invoice_data = array();

			// 電子發票類型.
			if ( $order->get_meta( '_invoice_type' ) && ! empty( $order->get_meta( '_invoice_type' ) ) ) {
				if ( 'personal' === $order->get_meta( '_invoice_type' ) ) {
					$invoice_data['_invoice_type'] = 'individual';
				} else {
					$invoice_data['_invoice_type'] = $order->get_meta( '_invoice_type' );
				}
			}

			// 個人發票類型.
			if ( $order->get_meta( '_invoice_carruer_type' ) && ! empty( $order->get_meta( '_invoice_carruer_type' ) ) ) {
				switch ( $order->get_meta( '_invoice_carruer_type' ) ) {
					case 'ecpay_host':
						$invoice_data['_invoice_individual'] = '1';
						break;
					case 'MOICA':
						$invoice_data['_invoice_individual'] = '2';
						break;
					case 'phone_barcode':
						$invoice_data['_invoice_individual'] = '3';
						break;
					default:
						// code...
						break;
				}
			}

			// 載具.
			if ( $order->get_meta( '_invoice_carruer_no' ) && ! empty( $order->get_meta( '_invoice_carruer_no' ) ) ) {
				$invoice_data['_invoice_carrier'] = $order->get_meta( '_invoice_carruer_no' );
			}

			// 統一編號.
			if ( $order->get_meta( '_invoice_no' ) && ! empty( $order->get_meta( '_invoice_no' ) ) ) {
				$invoice_data['_invoice_tax_id'] = $order->get_meta( '_invoice_no' );
			}

			// 公司名稱.
			if ( $order->get_meta( '_invoice_no' ) && empty( $invoice_data['_invoice_company_name'] ) && ! empty( $order->get_meta( '_invoice_no' ) ) ) {
				$options = array(
					'method'  => 'GET',
					'timeout' => 60,
					'headers' => array(
						'Content-Type' => 'application/json',
					),
				);

				$response = wp_remote_request( 'https://company.g0v.ronny.tw/api/show/' . $order->get_meta( '_invoice_no' ), $options );
				$resp     = json_decode( wp_remote_retrieve_body( $response ) );

				$invoice_data['_invoice_company_name'] = $resp->data->公司名稱;
			}

			if ( $order->get_meta( '_invoice_donate_no' ) && ! empty( $order->get_meta( '_invoice_donate_no' ) ) ) {
				$invoice_data['_invoice_donate'] = $order->get_meta( '_invoice_donate_no' );
			}

			if ( count( $invoice_data ) > 0 ) {
				$order->update_meta_data( '_ecpay_invoice_data', $invoice_data );
				$order->save();
			}
		}
	}

	/**
	 * 開立發票
	 */
	public function issue_invoice( $order_id ) {
		$order = \wc_get_order( $order_id );

		if ( ! isset( $order->get_meta( '_ecpay_invoice_status' )[0] ) || $order->get_meta( '_ecpay_invoice_status' )[0] == 0 ) {
			$invoice = new EcpayInvoiceHandler();
			$invoice->generate_invoice( $order_id );
		}
	}

	/**
	 * 作廢發票
	 */
	public function invalid_invoice( $order_id ) {
		$order = \wc_get_order( $order_id );
		if ( isset( $order->get_meta( '_ecpay_invoice_status' )[0] ) || $order->get_meta( '_ecpay_invoice_status' )[0] == 1 ) {
			$invoice = new EcpayInvoiceHandler();
			$invoice->invalid_invoice( $order_id );
		}
	}
}

Admin::init();
