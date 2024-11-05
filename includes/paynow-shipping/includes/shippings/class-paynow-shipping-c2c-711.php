<?php
/**
 * PayNow Shipping C2C 7-11 class file.
 *
 * @package paynow
 */

defined( 'ABSPATH' ) || exit;

/**
 * PayNow_Shipping_C2C_711 class.
 */
class PayNow_Shipping_C2C_711 extends PayNow_Abstract_Shipping_Method {

	/**
	 * Constructor
	 *
	 * @param integer $instance_id The instance id.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'paynow_shipping_c2c_711';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'PayNow Shipping C2C 7-11', 'paynow-shipping' );
		$this->method_description = __( 'PayNow Shipping C2C 7-11', 'paynow-shipping' );
		$this->logistic_service   = PayNow_Shipping_Logistic_Service::SEVEN;

		if ( empty( $this->instance_form_fields ) ) {
			$this->instance_form_fields = include PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/settings/settings-paynow-shipping-c2c-711.php';
		}

		$this->init();
	}
}
