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

		parent::__construct();

		$this->instance_id        = absint( $instance_id );
		$this->id                 = 'paynow_shipping_c2c_hilife';
		$this->method_title       = __( 'PayNow Shipping C2C Hi-Life', 'paynow-shipping' );
		$this->method_description = __( 'PayNow Shipping C2C Hi-Life', 'paynow-shipping' );
		$this->logistic_service   = PayNow_Shipping_Logistic_Service::HILIFE;

		$this->init();

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Caculate shipping fee.
	 *
	 * @param array $package The shipping package.
	 * @return void
	 */
	public function calculate_shipping( $package = array() ) {
		$rate = array(
			'id'      => $this->get_rate_id(),
			'label'   => $this->title,
			'cost'    => $this->get_cost(),
			'taxes'   => true,
			'package' => $package,
		);

		$this->add_rate( $rate );
		do_action( 'woocommerce_' . $this->id . '_shipping_add_rate', $this, $rate );
	}

	// FIXME: not working.
	/**
	 * Check if this shipping method available or not.
	 *
	 * @param array $package The shipping package array.
	 * @return boolean
	 */
	public function is_available( $package ) {

		$max_amount   = 20000;
		$is_available = $this->is_enabled();

<<<<<<< HEAD
		if ( 'no' === $this->enabled ) {
			return false;
		}

=======
>>>>>>> 93c3f937ddf0a76300ce8ce20470a3a6dd50499a
		$total = WC()->cart->get_cart_contents_total();
		if ( $total >= $max_amount ) {
			$is_available = false;
		}

		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this );
	}

	/**
	 * Initialize shipping method
	 *
	 * @return void
	 */
	public function init() {

		$this->init_settings();
		$this->instance_form_fields = include PAYNOW_SHIPPING_PLUGIN_DIR . 'includes/settings/settings-paynow-shipping-c2c-hilife.php';

		$this->title                    = $this->get_option( 'title' );
		$this->cost                     = $this->get_option( 'cost' );
		$this->free_shipping_requires   = $this->get_option( 'free_shipping_requires' );
		$this->free_shipping_min_amount = $this->get_option( 'free_shipping_min_amount', 0 );
	}
}
