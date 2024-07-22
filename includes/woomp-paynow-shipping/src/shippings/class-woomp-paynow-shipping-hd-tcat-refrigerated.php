<?php
/**
 * Class WOOMP_PayNow_Shipping_HD_TCat_Refrigerated file.
 *
 * @package woomp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WOOMP_PayNow_Shipping_HD_TCat_Refrigerated.
 *
 * @since      1.0.0
 * @package    woomp
 * @subpackage woomp/includes
 */
class WOOMP_PayNow_Shipping_HD_TCat_Refrigerated extends PayNow_Abstract_Shipping_Method {

	/**
	 * The constructor.
	 *
	 * @param integer $instance_id The shipping method instance id.
	 */
	public function __construct( $instance_id = 0 ) {

		parent::__construct();

		$this->instance_id        = absint( $instance_id );
		$this->id                 = 'woomp_paynow_shipping_hd_tcat_refrigerated';
		$this->method_title       = __( 'PayNow Shipping TCat Refrigerated', 'woomp' );
		$this->method_description = 'PayNow Shipping TCat Refrigerated';
		$this->logistic_service   = PayNow_Shipping_Logistic_Service::TCAT;

		$this->init();

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Initialize settings
	 *
	 * @return void
	 */
	public function init() {

		$this->instance_form_fields = include WOOMP_PAYNOW_SHIPPING_PLUGIN_DIR . 'src/settings/settings-woomp-paynow-shipping-hd-tcat-refrigerated.php';
		$this->init_settings();

		$this->title                    = $this->get_option( 'title' );
		$this->cost                     = $this->get_option( 'cost' );
		$this->free_shipping_requires   = $this->get_option( 'free_shipping_requires' );
		$this->free_shipping_min_amount = $this->get_option( 'free_shipping_min_amount', 0 );
		$this->type                     = $this->get_option( 'type', 'class' );
		$this->max_amount               = 100000;
	}
}
