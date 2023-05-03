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
		if ( ( wc_string_to_bool( get_option( 'wc_woomp_ezpay_invoice_testmode_enabled' ) ) ) ) {
			$account       = array(
				'merchantID' => get_option( 'wc_woomp_ezpay_invoice_merchant_id_test' ),
				'hashKey'    => get_option( 'wc_woomp_ezpay_invoice_hashkey_test' ),
				'hashIV'     => get_option( 'wc_woomp_ezpay_invoice_hashiv_test' ),
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
	 * Get issue data.
	 *
	 * @param int $order_id Order Id.
	 *
	 * @return array $issue_data Invoice issue data.
	 */
	private function get_issue_data( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( '0' === $order->get_total() || ! $order->get_meta( '_ezpay_invoice_data' ) ) {
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
			$product_name  .= $divide . preg_replace( '/[\s｜（）]+/u', '-', $item->get_name() );
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
		$issue_data['MerchantOrderNo'] = substr( get_option( 'wc_woomp_ezpay_invoice_order_prefix' ) . $order_id . '_' . time(), 0, 20 );
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
		$issue_data['ItemName']        = mb_substr( $product_name, 0, 30, 'UTF-8' ); // 商品名稱.
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

		return $issue_data;
	}

	/**
	 * 開立發票
	 */
	public function generate_invoice( $order_id ) {
		$order      = wc_get_order( $order_id );
		$issue_data = $this->get_issue_data( $order_id );

		if ( ! $issue_data ) {
			return;
		}

		$this->invoice->create( $issue_data );
		$result_data = $this->invoice->getResponse();

		if ( $this->invoice->isOK() ) {
			$order->update_meta_data( '_ezpay_invoice_result', $result_data );
			$order->update_meta_data( '_ezpay_invoice_number', $this->invoice->getResult( 'InvoiceNumber' ) );
			$order_note = "ezPay電子發票開立結果<br>回傳訊息：{$result_data->message}<br>回應代碼：{$result_data->code}";
			if ( 'SUCCESS' === $result_data->code ) {
				$order_note .= "<br>發票號碼：{$result_data->result->InvoiceNumber}";
				$order_note .= "<br>開立時間：{$result_data->result->CreateTime}";
			}
			$order->add_order_note( $order_note );
		} else {
			$order->update_meta_data( '_ezpay_invoice_result', $this->invoice->getErrorMessage()['message'] );
			$order->add_order_note( 'ezPay電子發票開立結果<br>回傳訊息：' . $this->invoice->getErrorMessage()['message'] );
		}
		$order->save();
		return '開立結果：' . esc_html( $result_data->message );
	}

	/**
	 * 作廢發票
	 */
	public function invalid_invoice( $order_id ) {
		$order          = wc_get_order( $order_id );
		$invoice_number = $order->get_meta( '_ezpay_invoice_number' );
		$result_data    = $order->get_meta( '_ezpay_invoice_result' );

		if ( $invoice_number ) {

			$this->invoice = $this->invoice->info(
				array(
					'SearchType'      => 0,
					'MerchantOrderNo' => $result_data->result->MerchantOrderNo,
					'InvoiceNumber'   => $result_data->result->InvoiceNumber,
					'RandomNum'       => $result_data->result->RandomNum,
				)
			);

			$this->invoice->invalid(
				array(
					'InvoiceNumber' => $this->invoice->getResult( 'InvoiceNumber' ), // 發票號碼.
					'InvalidReason' => '發票作廢', // 作廢原因.
					'RandomNum'     => $this->invoice->getResult( 'RandomNum' ),
				)
			);
			$result_data = $this->invoice->getResponse();

			if ( $this->invoice->isOK() ) {
				$order_note = "ezPay電子發票作廢結果<br>回傳訊息：發票作廢成功<br>回應代碼：{$result_data->code}";
				if ( 'SUCCESS' === $result_data->code ) {
					$order_note .= "<br>發票號碼：{$result_data->result->InvoiceNumber}";
					$order_note .= "<br>作廢時間：{$result_data->result->CreateTime}";
				}
				$order->add_order_note( $order_note );
				$order->update_meta_data( '_ezpay_invoice_number', '' );
				$order->update_meta_data( '_ezpay_invoice_result', '' );
				$order->save();
			} else {
				$order->add_order_note( 'ezPay電子發票作廢結果<br>回傳訊息：' . $this->invoice->getErrorMessage()['message'] . '<br>回傳代碼：' . $this->invoice->getErrorMessage()['code'] );
			}
			return '作廢結果：' . esc_html( '發票作廢成功' );
		}
	}
}
