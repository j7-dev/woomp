<?php
/**
 * LINEPay Gateway Constant
 *
 * Define the constant used in LINE Pay.
 *
 * @version 1.0.0
 * @author LINEPay
 */
final class WC_Gateway_LINEPay_Const {

	// resource.
	const RESOURCE_IMG_LOGO_OFFICIAL_PREFIX     = '/assets/images/logo/linepay_logo_';
	const RESOURCE_IMG_LOGO_OFFICIAL_THB_PREFIX = '/assets/images/logo/THB/linepay_logo_';
	const RESOURCE_IMG_LOGO_OFFICIAL_1          = '238x78.png';
	const RESOURCE_IMG_LOGO_OFFICIAL_2          = '119x39.png';
	const RESOURCE_IMG_LOGO_OFFICIAL_3          = '98x32.png';
	const RESOURCE_IMG_LOGO_OFFICIAL_4          = '85x28.png';
	const RESOURCE_IMG_LOGO_OFFICIAL_5          = '74x24.png';
	const RESOURCE_IMG_LOGO_OFFICIAL_6          = '61x20.png';

	const RESOURCE_JS_CUSTOMER_REFUND_ACTION = '/assets/js/customer-refund-action.js';
	const RESOURCE_JS_LOGO_UPLOADER          = '/assets/js/logo-uploader.js';

	// uri.
	const URI_RESERVE          = '/v3/payments/request';
	const URI_CONFIRM          = '/v3/payments/{transaction_id}/confirm';
	const URI_DETAILS          = '/v3/payments?transactionId={transaction_id}';
	// const URI_DETAILS          = '/v3/payments?orderId={order_id}';
	const URI_REFUND           = '/v3/payments/{transaction_id}/refund';
	const URI_CALLBACK_HANDLER = '/wc-api/wc_gateway_linepay_handler';

	// host.
	const HOST_SANDBOX = 'https://sandbox-api-pay.line.me';
	const HOST_REAL    = 'https://api-pay.line.me';

	// request type.
	const REQUEST_TYPE_RESERVE = 'reserve';
	const REQUEST_TYPE_CONFIRM = 'confirm';
	const REQUEST_TYPE_DETAILS = 'details';
	const REQUEST_TYPE_CANCEL  = 'cancel';
	const REQUEST_TYPE_REFUND  = 'refund';

	// environment.
	const ENV_SANDBOX = 'sandbox';
	const ENV_REAL    = 'real';

	// payment status.
	const PAYMENT_STATUS_RESERVED  = 'reserved';
	const PAYMENT_STATUS_CONFIRMED = 'confirmed';
	const PAYMENT_STATUS_CANCELLED = 'cancelled';
	const PAYMENT_STATUS_REFUNDED  = 'refunded';
	const PAYMENT_STATUS_FAILED    = 'failed';

	// payment action.
	const PAYMENT_ACTION_AUTH         = 'authorization';
	const PAYMENT_ACTION_AUTH_CAPTURE = 'authorization/capture';

	// payment type.
	const PAYMENT_TYPE_NORMAL      = 'NORMAL';
	const PAYMENT_TYPE_PREAPPROVED = 'PREAPPROVED';

	// user status.
	const USER_STATUS_ADMIN    = 'admin';
	const USER_STATUS_CUSTOMER = 'customer';

	// log template.
	const LOG_TEMPLATE_REFUND_FAILURE_AFTER_CONFIRM          = '[order_id: %s][requested confirm amount: %s][confirmed amount: %s] - %s';
	const LOG_TEMPLATE_CONFIRM_FAILURE_MISMATCH_ORDER_AMOUNT = '[requested confirm amount: %s][reserved amount: %s] - unvalid amount';
	const LOG_TEMPLATE_PAYMENT_CANCEL                        = '[order_id: %s][reserved_transaction_id: %s] - payment cancel';
	const LOG_TEMPLATE_HANDLE_CALLBANK_NOT_FOUND_ORDER_ID    = '[order_id: %s] - %s';
	const LOG_TEMPLATE_HANDLE_CALLBANK_NOT_FOUND_REQUREST    = '[order_id: %s][payment_status: %s][req_type: %s] - %s';
	const LOG_TEMPLATE_RESERVE_UNVALID_CURRENCY_SCALE        = '[order_id: %s][std_amount: %s][base currency: %s][base currency scale: %d][amount precision: %d] - unvalied currency scale';

	const AUTH_ALGRO             = 'sha256';
	const REQUEST_TIME_FORMAT    = 'YmdHis';
	const CONFIRM_URLTYPE_CLIENT = 'CLIENT';
}
