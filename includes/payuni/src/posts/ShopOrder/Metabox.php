<?php

namespace PAYUNI\Posts\ShopOrder;

defined( 'ABSPATH' ) || exit;

use ODS\Metabox as ODSMetabox;

class Metabox {

	private $metabox;

	public static function register() {
		$class = new self();
		add_action( 'admin_init', [ $class, 'set_metabox' ], 99 );
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

		if ( 'SUCCESS' === $order->get_meta( '_payuni_resp_status' ) ) {
			return;
		}

		if ( 'pending' !== $order->get_status() ) {
			return;
		}

		$this->metabox = new ODSMetabox(
			[
				'id'       => 'payuni_subscripiton',
				'title'    => __( '統一金流定期定額', 'woomp' ),
				'screen'   => 'shop_order',
				'context'  => 'side',
				'priority' => 'default',
			]
		);

		$this->metabox->addHtml(
			[
				'id'   => 'payuni_subscripiton_section',
				'html' => $this->set_request_button( $_GET['post'] ),
			],
		);
	}

	/**
	 * 建立重新請款按鈕
	 */
	private function set_request_button( $order_id ) {

		$order  = \wc_get_order( $order_id );
		$output = '<div style="margin-top:20px">';

		$output .= "<button class='button button-primary btnPayuniSubscriptionPayManual' type='button' value='" . $order_id . "'>手動扣款</button>";

		// 產生按鈕，傳送 order id 給ajax js
		// if ( ! $order->get_meta( '_ezpay_invoice_number' ) ) {
		// $output .= "<button class='button btnGenerateInvoiceEzPay' type='button' value='" . $order_id . "'>開立發票</button><button class='button save_order button-primary' id='btnUpdateInvoiceDataEzPay' type='submit' value='" . $order_id . "' disabled>更新發票資料</button>";
		// } else {
		// $output .= "<button class='button btnInvalidInvoiceEzPay' type='button' value='" . $order_id . "'>作廢發票</button>";
		// }

		$output .= '</div>';

		return $output;
	}
}

Metabox::register();
