<?php
/**
 * PayNow Shipping C2C Hi-Life class file.
 *
 * @package paynow
 */

defined( 'ABSPATH' ) || exit;

/**
 * PayNow_Shipping_C2C_Hilife class.
 */
class PayNow_Shipping_C2C_Hilife extends PayNow_Abstract_Shipping_Method {

	/**
	 * Constructor
	 *
	 * @param integer $instance_id The instance_id.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'paynow_shipping_c2c_hilife';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'PayNow Shipping C2C Hi-Life', 'paynow-shipping' );
		$this->method_description = __( 'PayNow Shipping C2C Hi-Life', 'paynow-shipping' );
		$this->logistic_service   = PayNow_Shipping_Logistic_Service::HILIFE;

		if ( empty( $this->instance_form_fields ) ) {
			$this->instance_form_fields = include PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/settings/settings-paynow-shipping-c2c-hilife.php';
		}

		$this->init();
	}
}
