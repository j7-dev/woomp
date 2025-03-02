<?php
/**
 * WOOMP_PayNow_Shipping_C2C_Family_Frozen class file
 *
 * @package woomp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WOOMP_PayNow_Shipping_C2C_Family_Frozen shipping method
 */
class WOOMP_PayNow_Shipping_C2C_Family_Frozen extends PayNow_Abstract_Shipping_Method {

	/**
	 * Constructor
	 *
	 * @param integer $instance_id The shipping method instance id.
	 */
	public function __construct( $instance_id = 0 ) {

		parent::__construct();

		$this->instance_id        = absint( $instance_id );
		$this->id                 = 'woomp_paynow_shipping_c2c_family_frozen';
		$this->method_title       = __( 'PayNow Shipping C2C Family Frozen', 'woomp' );
		$this->method_description = 'PayNow Shipping C2C Family Frozen';
		$this->logistic_service   = PayNow_Shipping_Logistic_Service::FAMIFROZEN_C2C;

		$this->init();

		add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'process_admin_options' ] );
	}

	/**
	 * Initialize settings
	 *
	 * @return void
	 */
	public function init() {

		$this->instance_form_fields = include WOOMP_PAYNOW_SHIPPING_PLUGIN_DIR . 'src/settings/settings-woomp-paynow-shipping-c2c-family-frozen.php';
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->measurement = $this->get_option( 'measurement', 's60' );
		$this->cost        = $this->get_option( 'cost' );
		$this->requires    = $this->get_option( 'requires' );
		$this->min_amount  = $this->get_option( 'min_amount', 0 );
		// $this->free_shipping_requires   = $this->get_option( 'free_shipping_requires' );
		// $this->free_shipping_min_amount = $this->get_option( 'free_shipping_min_amount', 0 );
		$this->type       = $this->get_option( 'type', 'class' );
		$this->max_amount = 20000;
		$this->supports   = [
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		];
	}
}
