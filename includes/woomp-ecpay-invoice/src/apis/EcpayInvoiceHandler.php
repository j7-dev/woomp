<?php
namespace WOOMPECPAYINVOICE\APIs;

use WOOMPECPAYINVOICE\APIs\EcpayInvoiceFiels;

defined( 'ABSPATH' ) || exit;

class EcpayInvoiceHandler {

	/**
	 * 開立發票
	 */
	public function generate_invoice( $order_id ) {

		$order       = wc_get_order( $order_id );
		$order_total = $order->get_total();
		$order_info  = $order->get_address();

		if ( '0' === $order_total ) {
			$order->add_order_note( __( 'Zero total fee without invoice', 'woomp' ) );
			return __( 'Zero total fee without invoice', 'woomp' );
		}

		// 訂購人資料.
		$customerName = $order_info['last_name'] . $order_info['first_name'];
		$orderEmail   = $order_info['email'];
		$orderPhone   = $order_info['phone'];
		$orderAddress = $order_info['country'] . $order_info['state'] . $order_info['city'] . $order_info['address_1'] . $order_info['address_2'];

		$customerIdentifier = EcpayInvoiceFields::get_meta( $order_id, 'tax_id' ); // 統一編號
		$invoiceType        = EcpayInvoiceFields::get_meta( $order_id, 'type' );

		$donation = ( $invoiceType == 'donate' ) ? 1 : 0; // 捐贈
		$donation = ( empty( $customerIdentifier ) ) ? $donation : 0; // 如果有寫統一發票號碼則無法捐贈
		$print    = 0;

			// 有打統一編號 強制列印.
		if ( ! empty( $customerIdentifier ) ) {
			$print = 1;

			// 有統一編號 則取得公司名稱.
			$sCompany_Name = EcpayInvoiceFields::get_meta( $order_id, 'company_name' ); // 公司名稱
			$customerName  = ( ! empty( $sCompany_Name ) ) ? $sCompany_Name : $customerName;
		}

		$loveCode    = EcpayInvoiceFields::get_meta( $order_id, 'donate' ); // 捐贈碼
		$carruerType = EcpayInvoiceFields::get_meta( $order_id, 'individual' ); // 載具
		$carruerType = ( $carruerType == 0 ) ? '' : $carruerType;

			// 無載具 強制列印
		if ( empty( $carruerType ) ) {
			$print = 1;
		}

			// 有捐贈項目 不允許列印
		if ( $donation == 1 ) {
			$print = 0;
		}
		$carruerNum = EcpayInvoiceFields::get_meta( $order_id, 'carrier' ); // 載具編號

		// 付款成功次數 第一次付款或沒有此欄位則設定為空值
		$totalSuccessTimes = '';
		if ( isset( $order_info['_total_success_times'][0] ) ) {
			$totalSuccessTimes = ( $orderInfo['_total_success_times'][0] == '' ) ? '' : $orderInfo['_total_success_times'][0];
		}

		$invoiceRemark = '';

		// 有打統一編號 強制列印發票
		if ( ! empty( $customerIdentifier ) ) {

			$print = 1;

			// // 有統一編號 則取得公司名稱
			$sCompany_Name = EcpayInvoiceFields::get_meta( $order_id, 'company_name' ); // 公司名稱
			$customerName  = ( ! empty( $sCompany_Name ) ) ? $sCompany_Name : $customerName;
		}

		// 無載具 強制列印
		if ( empty( $carruerType ) ) {

			$print = 1;
		}

		// 有捐贈項目 不允許列印
		if ( $donation == 1 ) {

			$print = 0;
		}

		try {

			$sMsg = '';
			// 1.載入SDK程式
			$ecpay_invoice = new \EcpayInvoice();

			// 2.寫入基本介接參數
			$ecpay_invoice->Invoice_Method = 'INVOICE';
			$ecpay_invoice->Invoice_Url    = $this->get_api_key()['request_url_issue'];
			$ecpay_invoice->MerchantID     = $this->get_api_key()['merchant_id'];
			$ecpay_invoice->HashKey        = $this->get_api_key()['hashkey'];
			$ecpay_invoice->HashIV         = $this->get_api_key()['hashiv'];

			// 取得商品資訊
			$items    = array();
			$itemsTmp = $order->get_items();

			// 商品資訊

			foreach ( $itemsTmp as $key1 => $value1 ) {

				$items[ $key1 ]['ItemName']   = wp_strip_all_tags( str_replace( '|', '-', $value1['name'] ), true ); // 商品名稱 ItemName
				$items[ $key1 ]['ItemCount']  = $value1['quantity']; // 數量 ItemCount
				$items[ $key1 ]['ItemAmount'] = round( $value1['total'] + $value1['total_tax'] ); // 小計 ItemAmount
				$items[ $key1 ]['ItemPrice']  = $items[ $key1 ]['ItemAmount'] / $items[ $key1 ]['ItemCount']; // 單價 ItemPrice
			}

			foreach ( $items as $key2 => $value2 ) {

				array_push(
					$ecpay_invoice->Send['Items'],
					array(
						'ItemName'    => str_replace( '|', '-', $value2['ItemName'] ),
						'ItemCount'   => $value2['ItemCount'],
						'ItemWord'    => '批',
						'ItemPrice'   => $value2['ItemPrice'],
						'ItemTaxType' => 1,
						'ItemAmount'  => $value2['ItemAmount'],
					)
				);
			}

			// 運費
			$shippingTotal = number_format( (float) $order->get_total_shipping() + (float) $order->get_shipping_tax(), wc_get_price_decimals(), '.', '' );

			if ( $shippingTotal != 0 ) {

				array_push(
					$ecpay_invoice->Send['Items'],
					array(
						'ItemName'    => '運費',
						'ItemCount'   => 1,
						'ItemWord'    => '式',
						'ItemPrice'   => $shippingTotal,
						'ItemTaxType' => 1,
						'ItemAmount'  => $shippingTotal,
					)
				);
			}

			// 測試用使用時間標記，正式上線只能單一不重複。
			$relateNumber = date( 'YmdHis' );

			$ecpay_invoice->Send['RelateNumber']       = get_option( 'wc_woomp_ecpay_invoice_order_prefix' ) . $relateNumber;
			$ecpay_invoice->Send['CustomerID']         = '';
			$ecpay_invoice->Send['CustomerIdentifier'] = $customerIdentifier;
			$ecpay_invoice->Send['CustomerName']       = $customerName;
			$ecpay_invoice->Send['CustomerAddr']       = $orderAddress;
			$ecpay_invoice->Send['CustomerPhone']      = $orderPhone;
			$ecpay_invoice->Send['CustomerEmail']      = $orderEmail;
			$ecpay_invoice->Send['ClearanceMark']      = '';
			$ecpay_invoice->Send['Print']              = $print;
			$ecpay_invoice->Send['Donation']           = $donation;
			$ecpay_invoice->Send['LoveCode']           = $loveCode;
			$ecpay_invoice->Send['CarruerType']        = $carruerType;
			$ecpay_invoice->Send['CarruerNum']         = $carruerNum;
			$ecpay_invoice->Send['TaxType']            = 1;
			$ecpay_invoice->Send['SalesAmount']        = $order_total;
			$ecpay_invoice->Send['InvoiceRemark']      = $invoiceRemark;
			$ecpay_invoice->Send['InvType']            = '07';
			$ecpay_invoice->Send['vat']                = '';

			// 4.送出
			$return_info = $ecpay_invoice->Check_Out();

			// 於備註區寫入發票資訊
			$invoice_date    = $return_info['InvoiceDate'];
			$invoice_number  = $return_info['InvoiceNumber'];
			$invoice_message = $return_info['RtnMsg'];

			$invocie_result = ( $invoice_date ) ? __( '<b>Invoice issue result</b>', 'woomp' ) : __( '<b>Invoice issue faild</b>', 'woomp' );

			$invocie_time   = ( $invoice_date ) ? __( '<br>Generate Time: ', 'woomp' ) . $invoice_date : '';
			$invocie_number = ( $invoice_date ) ? __( '<br>Invoice Number: ', 'woomp' ) . $invoice_number : '';
			$invoice_msg    = __( '<br>Invoice Message: ', 'woomp' ) . $invoice_message;
			$order->add_order_note( $invocie_result . $invocie_time . $invocie_number . $invoice_msg );

			if ( ! empty( $sMsg ) ) {
				$order->add_order_note( $sMsg );
			}

			// 寫入發票回傳資訊
			if ( isset( $return_info['RtnCode'] ) && $return_info['RtnCode'] == 1 ) {

				// 異動已經開立發票的狀態 1.已經開立 0.尚未開立
				$order->update_meta_data( '_ecpay_invoice_status', 1 );
				// 寫入發票號碼
				$order->update_meta_data( '_ecpay_invoice_number', $return_info['InvoiceNumber'] );
				$order->save();
			}

			return __( 'Generate Ecpay invoice issue finish!', 'woomp' );

		} catch ( Exception $e ) {
			// 例外錯誤處理.
			return new WP_Error( 'error', $e->getMessage() );
		}

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
					$_ecpay_invoice_number = '_ecpay_invoice_number' . $totalSuccessTimes;  // 欄位名稱 記錄發票號碼
				}

				// 異動已經開立發票的狀態 1.已經開立 0.尚未開立
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
