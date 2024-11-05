<?php
/**
 * PayNow Shipping TCAT class file.
 *
 * @package paynow
 */

defined( 'ABSPATH' ) || exit;

/**
 * PayNow_Shipping_HD_TCat class.
 */
class PayNow_Shipping_HD_TCat extends PayNow_Abstract_Shipping_Method {

	/**
	 * Constructor
	 *
	 * @param integer $instance_id The instance_id.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'paynow_shipping_hd_tcat';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'PayNow Shipping TCat', 'paynow-shipping' );
		$this->method_description = __( 'PayNow Shipping TCat', 'paynow-shipping' );
		$this->logistic_service   = PayNow_Shipping_Logistic_Service::TCAT;

		if ( empty( $this->instance_form_fields ) ) {
			$this->instance_form_fields = include PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/settings/settings-paynow-shipping-hd-tcat.php';
		}

		$this->init();
	}
}
