<?php

namespace WOOMPEZPAYINVOICE\ShopOrder;

use ODS\Metabox;

defined( 'ABSPATH' ) || exit;


class Field {

	private $metabox;

	public static function register() {
		$class = new self();
		add_action( 'admin_init', array( $class, 'set_metabox' ) );
	}
	public function __construct() {

	}
	/**
	 * 建立發票資訊欄位
	 */
	public function set_metabox() {

		if ( ! wc_string_to_bool( get_option( 'wc_woomp_enabled_ezpay_invoice' ) ) ) {
			return;
		}

		if ( ! isset( $_GET['post'] ) ) {
			return;
		}

		$order = wc_get_order( $_GET['post'] );

		if ( ! $order ) {
			return;
		}

		$product_type = '';

		foreach ( $order->get_items() as $item ) {
			$product_type = \WC_Product_Factory::get_product_type( $item->get_product_id() );
		}

		if ( '0' === $order->get_total() && strpos( $product_type, 'subscription' ) === false ) {
			return;
		}

		$this->metabox = new Metabox(
			array(
				'id'       => 'ezpay_invoice',
				'title'    => __( '藍新 ezPay 電子發票', 'woomp' ),
				'screen'   => 'shop_order',
				'context'  => 'side',
				'priority' => 'default',
			)
		);

		if ( ! $order->get_meta( '_ezpay_invoice_data' ) ) {
			$order->update_meta_data( '_ezpay_invoice_data', array() );
			$order->save();
		}

		$_invoice_type         = ( array_key_exists( '_ezpay_invoice_type', $order->get_meta( '_ezpay_invoice_data' ) ) ) ? $order->get_meta( '_ezpay_invoice_data' )['_ezpay_invoice_type'] : '';
		$_invoice_individual   = ( array_key_exists( '_ezpay_invoice_individual', $order->get_meta( '_ezpay_invoice_data' ) ) ) ? $order->get_meta( '_ezpay_invoice_data' )['_ezpay_invoice_individual'] : '';
		$_invoice_carrier      = ( array_key_exists( '_ezpay_invoice_carrier', $order->get_meta( '_ezpay_invoice_data' ) ) ) ? $order->get_meta( '_ezpay_invoice_data' )['_ezpay_invoice_carrier'] : '';
		$_invoice_company_name = ( array_key_exists( '_ezpay_invoice_company_name', $order->get_meta( '_ezpay_invoice_data' ) ) ) ? $order->get_meta( '_ezpay_invoice_data' )['_ezpay_invoice_company_name'] : '';
		$_invoice_tax_id       = ( array_key_exists( '_ezpay_invoice_tax_id', $order->get_meta( '_ezpay_invoice_data' ) ) ) ? $order->get_meta( '_ezpay_invoice_data' )['_ezpay_invoice_tax_id'] : '';
		$_invoice_donate       = ( array_key_exists( '_ezpay_invoice_donate', $order->get_meta( '_ezpay_invoice_data' ) ) ) ? $order->get_meta( '_ezpay_invoice_data' )['_ezpay_invoice_donate'] : '';

		$output  = '<p><strong>' . __( 'Invoice Type', 'woomp' ) . '</strong></p>';
		$output .= '
			<select name="_ezpay_invoice_type" style="display:block;width:100%;margin-top:-8px;">
				<option value="individual" ' . selected( $_invoice_type, 'individual', false ) . ' >' . __( 'individual', 'woomp' ) . '</option>
				<option value="company" ' . selected( $_invoice_type, 'company', false ) . ' >' . __( 'company', 'woomp' ) . '</option>
				<option value="donate" ' . selected( $_invoice_type, 'donate', false ) . ' >' . __( 'donate', 'woomp' ) . '</option>
			</select>
		';

		// 顯示個人發票類型
		if ( $_invoice_individual >= 0 ) {
			$output .= '<div id="ezPayInvoiceIndividual" style="display:none"><p><strong>' . __( 'Individual Invoice Type', 'woomp' ) . '</strong></p>';
			$output .= '<select name="_ezpay_invoice_individual" style="display:block;width:100%;margin-top:-8px;">';
			if ( get_option( 'wc_woomp_ezpay_invoice_carrier_type' ) ) {
				foreach ( get_option( 'wc_woomp_ezpay_invoice_carrier_type' ) as $key => $value ) {
					$output .= '<option value="' . $value . '" ' . selected( $_invoice_individual, $value, false ) . '>' . $value . '</option>';
				}
			}
			$output .= '</select>';

			$output .= '</p></div>';
		}

		// 顯示載具編號
		$output .= '<div id="ezPayInvoiceCarrier" style="display:none"><p><strong>' . __( 'Carrier Number', 'woomp' ) . '</strong></p>';
		$output .= '<p><input type="text" name="_ezpay_invoice_carrier" value="' . $_invoice_carrier . '" style="margin-top:-10px;width:100%" /><p></div>';

		// 顯示公司名稱
		$output .= '<div id="ezPayInvoiceCompanyName" style="display:none"><p><strong>' . __( 'Company Name', 'woomp' ) . '</strong></p>';
		$output .= '<p><input type="text" name="_ezpay_invoice_company_name" value="' . $_invoice_company_name . '" style="margin-top:-10px;width:100%" /><p></div>';

		// 顯示統一編號
		$output .= '<div id="ezPayInvoiceTaxId" style="display:none"><p><strong>' . __( 'TaxID', 'woomp' ) . '</strong></p>';
		$output .= '<p><input type="text" name="_ezpay_invoice_tax_id" value="' . $_invoice_tax_id . '" style="margin-top:-10px;width:100%" /><p></div>';

		// 顯示捐贈碼
		$output .= '<div id="ezPayInvoiceDonate" style="display:none"><p><strong>' . __( 'Donate Number', 'woomp' ) . '</strong></p>';
		$output .= '<p><input type="text" name="_ezpay_invoice_donate" value="' . $_invoice_donate . '" style="margin-top:-10px;width:100%" /><p></div>';

		$output .= $this->set_invoice_button( $_GET['post'] );

		$this->metabox->addHtml(
			array(
				'id'   => 'ezpay_invoice_section',
				'html' => $output,
			),
		);
	}

	/**
	 * 建立發票開立按鈕
	 */
	private function set_invoice_button( $order_id ) {

		$order  = \wc_get_order( $order_id );
		$output = '<div style="display:flex;justify-content:space-between">';

		// 產生按鈕，傳送 order id 給ajax js
		if ( empty( $order->get_meta( '_ezpay_invoice_number' ) ) ) {
			$output .= "<button class='button btnGenerateInvoiceEzPay' type='button' value='" . $order_id . "'>開立發票</button><button class='button save_order button-primary' id='btnUpdateInvoiceDataEzPay' type='submit' value='" . $order_id . "' disabled>更新發票資料</button>";
		} else {
			$output .= "<button class='button btnInvalidInvoiceEzPay' type='button' value='" . $order_id . "'>作廢發票</button>";
		}

		$output .= '</div>';

		return $output;
	}

}

Field::register();
