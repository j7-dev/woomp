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

		//dd(
		//	$this->invoice->isOK(),
		//	$this->invoice->getResponse(),
		//	$this->invoice->getResult(),
		//	$this->invoice->getErrorMessage()
		//);
	}

	/**
	 * 作廢發票
	 */
	public function invalid_invoice( $order_id ) {

		global $woocommerce, $post;

		$order        = new \WC_Order( $order_id );
		$orderStatus  = $order->get_status( $order_id );
		$orderInfo    = get_post_meta( $order_id );
		$relateNumber = date( 'YmdHis' );

		// 付款成功最後的一次 第一次付款或沒有此欄位則設定為空值
		// $totalSuccessTimes = ( isset( $orderInfo['_total_success_times'][0] ) && $orderInfo['_total_success_times'][0] == '' ) ? '' : $orderInfo['_total_success_times'][0];
		$totalSuccessTimes = '';

		// 已經開立發票才允許(找出最後一次)
		$_ecpay_invoice_status = '_ecpay_invoice_status' . $totalSuccessTimes;

		if ( isset( $orderInfo[ $_ecpay_invoice_status ][0] ) && $orderInfo[ $_ecpay_invoice_status ][0] == 1 ) {

			// 發票號碼
			$_ecpay_invoice_number = '_ecpay_invoice_number' . $totalSuccessTimes;
			$invoice_number        = get_post_meta( $order_id, $_ecpay_invoice_number, true );

			// 呼叫SDK 作廢發��
			try {

				$msg = '';

				$ecpayInvoice = new ECPay_Woo_EcpayInvoice();

				// 2.介接參數
				$ecpayInvoice->Invoice_Method = 'INVOICE_VOID';
				$ecpayInvoice->Invoice_Url    = $this->get_api_key()['request_url_invalid'];
				$ecpayInvoice->MerchantID     = $this->get_api_key()['merchant_id'];
				$ecpayInvoice->HashKey        = $this->get_api_key()['hashkey'];
				$ecpayInvoice->HashIV         = $this->get_api_key()['hashiv'];

				// 3.寫入發票相關資訊
				$ecpayInvoice->Send['InvoiceNumber'] = $invoice_number;
				$ecpayInvoice->Send['Reason']        = '發票作廢';

				// 4.送��
				$return_info = $ecpayInvoice->Check_Out();

			} catch ( Exception $e ) {

				// 例外錯誤處理。
				$msg = $e->getMessage();
			}

			// 於備註區寫入發票資訊
			$invoice_number  = $return_info['InvoiceNumber'];
			$invoice_message = $return_info['RtnMsg'];
			$invoiceNote     = __( '<b>Ecpay invalid invoice result</b>', 'woomp' ) . __( '<br>Invoice Number: ', 'woomp' ) . $invoice_number . __( '<br>Invoice Message: ', 'woomp' ) . $invoice_message;
			$order->add_order_note( $invoiceNote );

			if ( ! empty( $sMsg ) ) {
				$order->add_order_note( $sMsg );
			}

			if ( isset( $return_info['RtnCode'] ) && $return_info['RtnCode'] == 1 ) {

				if ( empty( $totalSuccessTimes ) ) {
					$_ecpay_invoice_stauts = '_ecpay_invoice_status';     // 欄位名稱 記錄狀態
					$_ecpay_invoice_number = '_ecpay_invoice_number';     // 欄位名稱 記錄發票號碼
				} else {
					$_ecpay_invoice_stauts = '_ecpay_invoice_status' . $totalSuccessTimes;  // 欄位 記錄狀態
					$_ecpay_invoice_number = '_ecpay_invoice_number' . $totalSuccessTimes;  // 欄位��稱 記錄發票號碼
				}

				// 異動已經開立發票的狀態 1.已經開�� 0.尚未開立
				$order->update_meta_data( $_ecpay_invoice_stauts, 0 );

				// 清除發票號碼
				$order->update_meta_data( $_ecpay_invoice_number, '' );

				$order->save();
			}

			return __( 'Invalid Ecpay invoice successful!', 'woomp' );
		} else {
			return __( 'Invalid Ecpay invoice error!', 'woomp' );
		}
	}

	/**
	 * 取得 API 資料
	 */
	private function get_api_key() {
		$api_data = array();

		if ( wc_string_to_bool( get_option( 'wc_woomp_ecpay_invoice_testmode_enabled' ) ) ) {
			$api_data['request_url_issue']   = 'https://einvoice-stage.ecpay.com.tw/Invoice/Issue';
			$api_data['request_url_invalid'] = 'https://einvoice-stage.ecpay.com.tw/Invoice/IssueInvalid';
			$api_data['merchant_id']         = '2000132';
			$api_data['hashkey']             = 'ejCk326UnaZWKisg';
			$api_data['hashiv']              = 'q9jcZX8Ib9LM8wYk';
		} else {
			$api_data['request_url_issue']   = 'https://einvoice.ecpay.com.tw/Invoice/Issue';
			$api_data['request_url_invalid'] = 'https://einvoice.ecpay.com.tw/Invoice/IssueInvalid';

			if ( get_option( 'RY_WEI_ecpay_MerchantID' ) && get_option( 'RY_WEI_ecpay_HashKey' ) && get_option( 'RY_WEI_ecpay_HashIV' ) ) {
				$api_data['merchant_id'] = get_option( 'RY_WEI_ecpay_MerchantID' );
				$api_data['hashkey']     = get_option( 'RY_WEI_ecpay_HashKey' );
				$api_data['hashiv']      = get_option( 'RY_WEI_ecpay_HashIV' );
			} else {
				$api_data['merchant_id'] = get_option( 'wc_woomp_ecpay_invoice_merchant_id' );
				$api_data['hashkey']     = get_option( 'wc_woomp_ecpay_invoice_hashkey' );
				$api_data['hashiv']      = get_option( 'wc_woomp_ecpay_invoice_hashiv' );
			}
		}

		return $api_data;
	}
}
