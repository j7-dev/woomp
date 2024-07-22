<?php
/**
 * PayNow pay type
 *
 * @package paynow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings for Paynow Payment Gateway
 */
class PayNow_Pay_Type {

	const CREDIT          = '01';
	const WEBATM          = '02';
	const VIRTUAL_ACCOUNT = '03';
	const IBON            = '05';
	const UNION           = '09';
	const BARCODE         = '10';
	const CREDIT_INSTALL  = '11';

	/**
	 * Get pay type name by type number
	 *
	 * @param string $type_no The pay_type no.
	 * @return string
	 */
	public static function get_type_name( $type_no ) {
		$type_name = '';
		switch ( $type_no ) {
			case '01':
				$type_name = 'CREDIT';
				break;
			case '02':
				$type_name = 'WEBATM';
				break;
			case '03':
				$type_name = 'VIRTUAL_ACCOUNT';
				break;
			case '05':
				$type_name = 'IBON';
				break;
			case '10':
				$type_name = 'BARCODE';
				break;
			case '11':
				$type_name = 'CREDIT_INSTALL';
				break;
			default:
				$type_name = '無法判斷的付款方式：' . $type_no;
				break;
		}
		return $type_name;
	}
}
