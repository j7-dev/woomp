<?php

class PayNow_Shipping_Status {

	const AT_SENDER_CVS   = '0101'; // 商品已到寄件門市.
	const DELIVERING      = '5202'; // 交貨便收件.
	const EC_RETURN       = '5201'; // EC 收退.
	const AT_RECEIVER_CVS = '5000'; // 取件門市配達.
	const CUSTOMER_PICKUP = '8000'; // 買家已取件.
	const TCAT_RETURN     = '8520'; // 黑貓收退.
}
