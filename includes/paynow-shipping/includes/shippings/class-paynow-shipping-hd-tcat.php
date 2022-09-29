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

		parent::__construct();

		$this->instance_id        = absint( $instance_id );
		$this->id                 = 'paynow_shipping_hd_tcat';
		$this->method_title       = __( 'PayNow Shipping TCat', 'paynow-shipping' );
		$this->method_description = __( 'PayNow Shipping TCat', 'paynow-shipping' );
		$this->logistic_service   = PayNow_Shipping_Logistic_Service::TCAT;

		$this->init();

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

	}

	/**
	 * Initialize shipping method
	 *
	 * @return void
	 */
	public function init() {

		$this->instance_form_fields = include PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/settings/settings-paynow-shipping-hd-tcat.php';
		$this->init_settings();

		$this->title                    = $this->get_option( 'title' );
		$this->cost                     = $this->get_option( 'cost' );
		$this->free_shipping_requires   = $this->get_option( 'free_shipping_requires' );
		$this->free_shipping_min_amount = $this->get_option( 'free_shipping_min_amount', 0 );
		$this->type                     = $this->get_option( 'type', 'class' );
		$this->max_amount               = 100000;

	}

}
