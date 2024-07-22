<?php
/**
 * PayNow Logistic Service class file.
 *
 * @package paynow
 */

defined( 'ABSPATH' ) || exit;

/**
 * PayNow_Shipping_Logistic_Service class
 */
class PayNow_Shipping_Logistic_Service {
	const SEVEN           = '01'; // 7-11 店到店.
	const SEVENBULK       = '02'; // 7-11 大宗.
	const FAMI            = '03'; // 全家 店到店.
	const FAMIBULK        = '04'; // 全家 大宗.
	const HILIFE          = '05'; // HiLife 店到店.
	const TCAT            = '06'; // 黑貓 宅配.
	const SEVENFROZEN_C2C = '21'; // 7-11 交貨便(冷凍).
	const SEVENFROZEN     = '22'; // 7-11 大宗冷凍.
	const FAMIFROZEN_C2C  = '23'; // 全家 店到店(冷凍).
	const FAMIFROZEN      = '24'; // 全家 大宗冷凍.

	/**
	 * Check if the service need cvs
	 *
	 * @param string $service_id The logistic service id.
	 * @return boolean
	 */
	public static function is_cvs( $service_id ) {

		if ( self::TCAT !== $service_id ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get logistic service id by shipping method id
	 *
	 * @param string $method_id shipping method id.
	 * @return string
	 */
	public static function get_service_id( $method_id ) {

		switch ( $method_id ) {
			case 'paynow_shipping_c2c_711':
				return self::SEVEN;
			break;
			case 'paynow_shipping_b2c_711':
				return self::SEVENBULK;
			break;
			case 'paynow_shipping_b2c_711_frozen':
				return self::SEVENFROZEN;
			break;
			case 'paynow_shipping_c2c_family':
				return self::FAMI;
			break;
			case 'paynow_shipping_b2c_family':
				return self::FAMIBULK;
			break;
			case 'paynow_shipping_b2c_family_frozen':
				return self::FAMIFROZEN;
			break;
			case 'paynow_shipping_c2c_hilife':
				return self::HILIFE;
			break;
			case 'paynow_shipping_hd_tcat':
				return self::TCAT;
			break;
			case 'paynow_shipping_hd_tcat_frozen':
				return self::TCAT;
			break;
			case 'paynow_shipping_hd_tcat_refrigerated':
				return self::TCAT;
			break;
			case 'woomp_paynow_shipping_c2c_711_frozen':
				return self::SEVENFROZEN_C2C;
			break;
			case 'woomp_paynow_shipping_c2c_family_frozen':
				return self::FAMIFROZEN_C2C;
			break;
			default:
				return '';
		}
	}
}
