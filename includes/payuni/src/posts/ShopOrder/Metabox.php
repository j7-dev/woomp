<?php

namespace PAYUNI\Posts\ShopOrder;

defined( 'ABSPATH' ) || exit;

use ODS\Metabox as ODSMetabox;

class Metabox {

	private $metabox;

	public static function register() {
		$class = new self();
		add_action( 'admin_init', array( $class, 'set_metabox' ), 99 );
	}
	
	/**
	 * 建立發票資訊欄位
	 */
	public function set_metabox() {

		if ( ! wc_string_to_bool( get_option( 'wc_woomp_enabled_payuni_gateway' ) ) ) {
			return;
		}

		if ( ! isset( $_GET['post'] ) ) {
			return;
		}

		$order = wc_get_order( $_GET['post'] );

		if ( ! $order ) {
			return;
		}

		if ( 'payuni-credit-subscription' !== $order->get_payment_method() ) {
			return;
		}

		$this->metabox = new ODSMetabox(
			array(
				'id'       => 'payuni_subscripiton',
				'title'    => __( '統一金流定期定額', 'woomp' ),
				'screen'   => 'shop_order',
				'context'  => 'side',
				'priority' => 'default',
			)
		);

		$this->metabox->addHtml(
			array(
				'id'   => 'payuni_subscripiton_section',
				'html' => $this->set_invoice_button( $_GET['post'] ),
			),
		);
	}

	/**
	 * 建立發票開立按鈕
	 */
	private function set_invoice_button( $order_id ) {

		$order  = \wc_get_order( $order_id );
		$output = '<div style="margin-top:20px">';

		$output .= "<button class='button btnPayuniSubscriptionCheck' type='button' value='" . $order_id . "'>查詢扣款狀態</button>";

		// 產生按鈕，傳送 order id 給ajax js
		// if ( ! $order->get_meta( '_ezpay_invoice_number' ) ) {
		// $output .= "<button class='button btnGenerateInvoiceEzPay' type='button' value='" . $order_id . "'>開立發票</button><button class='button save_order button-primary' id='btnUpdateInvoiceDataEzPay' type='submit' value='" . $order_id . "' disabled>更新發票資料</button>";
		// } else {
		// $output .= "<button class='button btnInvalidInvoiceEzPay' type='button' value='" . $order_id . "'>作廢發票</button>";
		// }

		$output .= '</div>';

		return $output;
	}

	/**
	 * 已開立發票禁止編輯
	 */
	private function set_edit_disable_style( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order->get_meta( '_ezpay_invoice_number' ) ) {
			return 'pointer-events:none;border:0;appearance:none;background-image:none;background-color:#efefef;';
		}
	}

}

Metabox::register();
