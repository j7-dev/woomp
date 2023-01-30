<?php
namespace WOOMPEZPAYINVOICE\APIs;

use InvoicePorter\EzpayInvoice;

defined( 'ABSPATH' ) || exit;

class EzPayInvoiceHandler {

	/**
	 * EzPay invoice object
	 *
	 * @var object
	 */
	private $invoice;


	/**
	 * Construct
	 */
	public function __construct() {
		if ( get_option( 'wc_woomp_ezpay_invoice_testmode_enabled' ) ) {
			$account       = array(
				'merchantID' => '32365158',
				'hashKey'    => 'qJtVQeJtMBqKh49gYLr6hUpsZNEZ8ucz',
				'hashIV'     => 'CRNTAlz9rdtGfGsP',
			);
			$is_production = false;
		} else {
			$account       = array(
				'merchantID' => get_option( 'wc_woomp_ezpay_invoice_merchant_id' ),
				'hashKey'    => get_option( 'wc_woomp_ezpay_invoice_hashkey' ),
				'hashIV'     => get_option( 'wc_woomp_ezpay_invoice_hashiv' ),
			);
			$is_production = true;
		}
		$this->invoice = new EzpayInvoice( $account, $is_production );
	}

	/**
	 * 開立發票
	 */
	public function generate_invoice( $order_id ) {

		$order       = wc_get_order( $order_id );
		$order_total = $order->get_total();
		$issue_data  = array();

		if ( '0' === $order_total || ! $order->get_meta( '_ezpay_invoice_data' ) ) {
			return;
		}

		// 取得顧客發票資訊.
		$invoice_type       = ( array_key_exists( '_ezpay_invoice_type', $order->get_meta( '_ezpay_invoice_data' ) ) ) ? $order->get_meta( '_ezpay_invoice_data' )['_ezpay_invoice_type'] : '';
		$invoice_individual = ( array_key_exists( '_ezpay_invoice_individual', $order->get_meta( '_ezpay_invoice_data' ) ) ) ? $order->get_meta( '_ezpay_invoice_data' )['_ezpay_invoice_individual'] : '';

		switch ( $invoice_individual ) {
			case '手機代碼':
				$invoice_individual = 0;
				break;
			case '自然人憑證':
				$invoice_individual = 1;
				break;
			case 'ezPay 電子發票載具':
				$invoice_individual = 2;
				break;
			default:
				$invoice_individual = '';
				break;
		}

		$invoice_carrier      = ( array_key_exists( '_ezpay_invoice_carrier', $order->get_meta( '_ezpay_invoice_data' ) ) ) ? $order->get_meta( '_ezpay_invoice_data' )['_ezpay_invoice_carrier'] : '';
		$invoice_company_name = ( array_key_exists( '_ezpay_invoice_company_name', $order->get_meta( '_ezpay_invoice_data' ) ) ) ? $order->get_meta( '_ezpay_invoice_data' )['_ezpay_invoice_company_name'] : '';
		$invoice_tax_id       = ( array_key_exists( '_ezpay_invoice_tax_id', $order->get_meta( '_ezpay_invoice_data' ) ) ) ? $order->get_meta( '_ezpay_invoice_data' )['_ezpay_invoice_tax_id'] : '';
		$invoice_donate       = ( array_key_exists( '_ezpay_invoice_donate', $order->get_meta( '_ezpay_invoice_data' ) ) ) ? $order->get_meta( '_ezpay_invoice_data' )['_ezpay_invoice_donate'] : '';

		// 取得商品相關資訊.
		$i             = 0;
		$product_name  = '';
		$product_count = '';
		$product_unit  = '';
		$product_price = '';
		$product_amt   = '';

		foreach ( $order->get_items() as $item ) {
			// print_r($item);
			$divide         = ( $i > 0 ) ? '|' : '';
			$product        = $item->get_product();
			$product_name  .= $divide . str_replace( ' ', '', $item->get_name() );
			$product_count .= $divide . str_replace( ' ', '', $item->get_quantity() );
			$product_unit  .= $divide . '件';

			if ( 'company' === $invoice_type ) {
				// B2B 未稅金額.
				$product_price .= $divide . round( $product->get_price() / 1.05 );
				$product_amt   .= $divide . round( $product->get_price() / 1.05 ) * $item->get_quantity();
			} else {
				// B2C 含稅金額.
				$product_price .= $divide . $product->get_price();
				$product_amt   .= $divide . $product->get_price() * $item->get_quantity();
			}

			$i++;
		}

		/**
		 * B2C
		 */
		$issue_data['MerchantOrderNo'] = get_option( 'wc_woomp_ezpay_invoice_order_prefix' ) . $order_id;
		$issue_data['Status']          = 1;
		$issue_data['Category']        = 'B2C';
		$issue_data['BuyerName']       = $order->get_billing_last_name() . $order->get_billing_first_name();
		$issue_data['BuyerAddress']    = $order->get_billing_postcode() . $order->get_billing_country() . $order->get_billing_state() . $order->get_billing_city() . $order->get_billing_address_1() . $order->get_billing_address_2();
		$issue_data['BuyerEmail']      = $order->get_billing_email();
		$issue_data['CarrierType']     = $invoice_individual;
		$issue_data['CarrierNum']      = ( 2 === $invoice_individual ) ? rawurlencode( $order->get_billing_email() ) : $invoice_carrier;
		$issue_data['PrintFlag']       = ( '' === $invoice_individual && '' === $invoice_donate ) ? 'Y' : 'N'; // 索取紙本發票.
		$issue_data['TaxType']         = 1; // 課稅別.
		$issue_data['TaxRate']         = 5; // 稅率5%.
		$issue_data['Amt']             = round( $order->get_total() / 1.05 ); // 未稅.
		$issue_data['TaxAmt']          = $order->get_total() - round( $order->get_total() / 1.05 ); // 稅額.
		$issue_data['TotalAmt']        = $order->get_total(); // 發票金額.
		$issue_data['ItemName']        = $product_name; // 商品名稱.
		$issue_data['ItemCount']       = $product_count; // 商品數量.
		$issue_data['ItemUnit']        = $product_unit; // 商品單位.
		$issue_data['ItemPrice']       = $product_price; // 商品單價.
		$issue_data['ItemAmt']         = $product_amt; // 商品小計.

		if ( 2 === $invoice_individual ) {
			$issue_data['KioskPrintFlag'] = 1;
		}

		/**
		 * B2B
		 */
		if ( 'company' === $invoice_type ) {
			$issue_data['Category']    = 'B2B';
			$issue_data['BuyerName']   = $invoice_company_name;
			$issue_data['BuyerUBN']    = $invoice_tax_id;
			$issue_data['CarrierType'] = '';
			$issue_data['PrintFlag']   = 'Y';
		}

		/**
		 * Donate
		 */
		if ( 'donate' === $invoice_type ) {
			$issue_data['CarrierType'] = '';
			$issue_data['LoveCode']    = $invoice_donate;
		}

		$this->invoice->create( $issue_data );

		if ( $this->invoice->isOK() ) {
			$result_data = $this->invoice->getResult();
			$order->update_meta_data( '_ezpay_invoice_result', $result_data );
			$order->add_order_note( json_encode( $result_data ) );
		} else {
			$order->update_meta_data( '_ezpay_invoice_result', $this->invoice->getErrorMessage() );
			$order->add_order_note( json_encode( $this->invoice->getErrorMessage() ) );
		}
		$order->save()

		// dd(
		// $this->invoice->isOK(),
		// $this->invoice->getResponse(),
		// $this->invoice->getResult(),
		// $this->invoice->getErrorMessage()
		// );
	}

	/**
	 * 作廢發票
	 */
	public function invalid_invoice( $order_id ) {
		$order          = wc_get_order( $order_id );
		$invoice_result = $order->get_meta( '_ezpay_invoice_result' );

		if ( 'SUCCESS' === $invoice_result['Status'] ) {
			$this->invoice->invalid(
				array(
					'InvoiceNumber' => $invoice_result['InvoiceNumber'], // 發票號碼.
					'InvalidReason' => '訂單取消', // 作廢原因.
				)
			);
		}
	}
}
