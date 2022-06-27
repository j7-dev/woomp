<?php
/**
 * WOOMP_PayNow_Shipping_HD_TCat_Frozen class file
 *
 * @package woomp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WOOMP_PayNow_Shipping_HD_TCat_Frozen shipping method
 */
class WOOMP_PayNow_Shipping_HD_TCat_Frozen extends PayNow_Abstract_Shipping_Method {

	/**
	 * Constructor
	 *
	 * @param integer $instance_id The shipping method instance id.
	 */
	public function __construct( $instance_id = 0 ) {

		parent::__construct();

		$this->instance_id        = absint( $instance_id );
		$this->id                 = 'woomp_paynow_shipping_hd_tcat_frozen';
		$this->method_title       = __( 'PayNow Shipping TCat Frozen', 'woomp' );
		$this->method_description = 'PayNow Shipping TCat Frozen';
		$this->logistic_service   = PayNow_Shipping_Logistic_Service::TCAT;

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
		/**
		 * Allow to filter the shipping rates
		 *
		 * @since 1.0.0
		 *
		 * @param WOOMP_PayNow_Shipping_HD_TCat_Frozen $this The shipping method instance.
		 * @param array                                $rate The shipping rate.
		 */
		do_action( 'woocommerce_' . $this->id . '_shipping_add_rate', $this, $rate );

	}

	/**
	 * Check if shipping method abvailable
	 *
	 * @param array $package Shipping package.
	 * @return boolean
	 */
	public function is_available( $package ) {

		$max_amount = 20000;

		if ( 'no' === $this->enabled ) {
			return false;
		}

		$total = WC()->cart->get_total();
		if ( $total >= $max_amount ) {
			return false;
		}

		$is_available = true;

		if ( $is_available ) {
			$shipping_classes = WC()->shipping->get_shipping_classes();
			if ( ! empty( $shipping_classes ) ) {
				$found_shipping_class = array();
				foreach ( $package['contents'] as $item_id => $values ) {
					if ( $values['data']->needs_shipping() ) {
						$shipping_class_slug = $values['data']->get_shipping_class();
						$shipping_class      = get_term_by( 'slug', $shipping_class_slug, 'product_shipping_class' );
						if ( $shipping_class && $shipping_class->term_id ) {
							$found_shipping_class[ $shipping_class->term_id ] = true;
						}
					}
				}
				foreach ( $found_shipping_class as $shipping_class_term_id => $value ) {
					if ( 'yes' !== $this->get_option( 'class_available_' . $shipping_class_term_id, 'yes' ) ) {
						$is_available = false;
						break;
					}
				}
			}
		}

		/**
		 * Allow to filter if the shipping method is available or not.
		 *
		 * @since 1.0.0
		 *
		 * @param boolean                                    $is_available If the shipping method is available or not.
		 * @param array                                      $package The shipping package.
		 * @param WOOMP_PayNow_Shipping_HD_TCat_Frozen $this The shipping method instance.
		 */
		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this );
	}

	/**
	 * Initialize settings
	 *
	 * @return void
	 */
	public function init() {

		$this->instance_form_fields = include WOOMP_PAYNOW_SHIPPING_PLUGIN_DIR . 'src/settings/settings-woomp-paynow-shipping-hd-tcat-frozen.php';
		$this->init_settings();

		$this->title                    = $this->get_option( 'title' );
		$this->cost                     = $this->get_option( 'cost' );
		$this->free_shipping_requires   = $this->get_option( 'free_shipping_requires' );
		$this->free_shipping_min_amount = $this->get_option( 'free_shipping_min_amount', 0 );

	}

}
