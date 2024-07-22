<?php
/**
 * PayNow_Shipping_Order_Meta class file.
 *
 * @package paynow
 */

defined( 'ABSPATH' ) || exit;

/**
 * The paynow orer meta field name.
 */
class PayNow_Shipping_Order_Meta {

	// phpcs:disable
	const LogisticServiceId = '_paynow_shipping_logistic_service_id';// 物流服務 id.
	const LogisticService   = '_paynow_shipping_logistic_service'; // 物流服務名稱.
	const SNO               = '_paynow_shipping_logistic_sno'; // 物流單序號.
	const StoreId           = '_shipping_paynow_storeid';
	const StoreName         = '_shipping_paynow_storename';
	const StoreAddr         = '_shipping_paynow_storeaddress';
	const ReservedNo        = '_shipping_paynow_reservedno'; // 保留編號.
	const ShipDate          = '_shipping_paynow_shipdate'; // 預計運送時間.
	const DeliveryType      = '_paynow_shipping_delivery_type';// 溫層 for 黑貓.
	const LogisticNumber    = '_paynow_shipping_logistic_number'; // paynow 物流單號.
	const PaymentNo         = '_paynow_shipping_paymentno'; // 物流商託運單號.
	const ValidationNo      = '_paynow_shipping_validation_no'; // 物流商驗證碼如需使用 IboN 列印請搭配 paymentno 使用.
	const Status            = '_paynow_shipping_status'; // 0:成立中訂單 1:無效訂單.
	const RenewOrderNo      = '_paynow_shipping_paynoworderno'; // 重新取號後訂單在 Paynow 的訂單編號
	const DeliveryStatus    = '_paynow_shipping_delivery_status'; // 物流狀態.
	const LogisticCode      = '_paynow_shipping_logistic_code'; // paynow 物流代碼.
	const DetailStatusDesc  = '_paynow_shipping_detail_status_desc'; // paynow 物流代碼描述.
	const ReturnMsg         = '_paynow_shipping_return_msg'; // 回傳訊息.
	const ErrorMsg          = '_paynow_shipping_error_msg'; // 錯誤訊息.
	const StatusUpdateAt    = '_paynow_shipping_status_update_at'; // 狀態更新時間.
	const StoreDate         = '_paynow_shipping_store_date'; // 到店日期.
	const StoreTime         = '_paynow_shipping_store_time'; // 到店時間.
	// phpcs:enable
}
