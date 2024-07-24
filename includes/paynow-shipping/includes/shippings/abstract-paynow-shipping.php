<?php
/**
 * Abstract PayNow shipping method
 *
 * PayNow_Shipping_Method abstract class file.
 *
 * @package paynow
 */

defined( 'ABSPATH' ) || exit;

/**
 * The PayNow shipping base payment method.
 */
abstract class PayNow_Abstract_Shipping_Method extends WC_Shipping_Method {

	/**
	 * Javascript data for api call.
	 *
	 * @var array
	 */
	public static $js_data;

	/**
	 * Logistic service.
	 *
	 * @see PayNow_Shipping_Logistic_Service
	 * @var string
	 */
	public $logistic_service;

	/**
	 * Minimum order amount for free shipping.
	 *
	 * @var int
	 */
	public $free_shipping_requires;

	/**
	 * Minimum order amount for free shipping.
	 *
	 * @var int
	 */
	public $free_shipping_min_amount;

	/**
	 * Max order amount that can use this shipping method.
	 *
	 * @var int
	 */
	public $max_amount;

	/**
	 * Constructor function
	 */
	public function __construct() {
		$this->supports = [
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		];
	}

	/**
	 * Check if shipping method abvailable
	 *
	 * @param array $package The shipping package.
	 * @return boolean
	 */
	public function is_available( $package ) {

		$is_available = $this->is_enabled();

		$total = WC()->cart->get_cart_contents_total();
		if ( isset( $this->max_amount ) && $total >= $this->max_amount ) {
			$is_available = false;
		}

		if ( $is_available ) {
			$shipping_classes = WC()->shipping->get_shipping_classes();
			if ( ! empty( $shipping_classes ) ) {
				$found_shipping_class = [];
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
		 * @param boolean                         $is_available If the shipping method is available or not.
		 * @param array                           $package The shipping package.
		 * @param PayNow_Abstract_Shipping_Method $this The shipping method instance.
		 */
		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this );
	}


	/**
	 * Caculate shipping fee.
	 *
	 * @param array $package The shipping package.
	 * @return void
	 */
	public function calculate_shipping( $package = [] ) {

		$rate = [
			'id'      => $this->get_rate_id(),
			'label'   => $this->title,
			'cost'    => 0,
			'package' => $package,
		];

		$has_costs = false; // True when a cost is set. False if all costs are blank strings.
		$cost      = $this->get_option( 'cost' );

		if ( '' !== $cost ) {
			$has_costs    = true;
			$rate['cost'] = $this->evaluate_cost(
				$cost,
				[
					'qty'  => $this->get_package_item_qty( $package ),
					'cost' => $package['contents_cost'],
				]
			);
		}

		$met_free_shipping = false;
		if ( 'min_amount' === $this->free_shipping_requires ) {
			$total = WC()->cart->get_displayed_subtotal();

			if ( WC()->cart->display_prices_including_tax() ) {
				$total = $total - WC()->cart->get_discount_tax();
			}

			$total = round( $total, wc_get_price_decimals() );

			if ( $total >= $this->free_shipping_min_amount ) {
				$met_free_shipping = true;
			} else {
				$met_free_shipping = false;
			}
		}

		// Add shipping class costs.
		$shipping_classes = WC()->shipping()->get_shipping_classes();

		if ( ! empty( $shipping_classes ) ) {
			$found_shipping_classes = $this->find_shipping_classes( $package );
			$highest_class_cost     = 0;

			foreach ( $found_shipping_classes as $shipping_class => $products ) {
				// Also handles BW compatibility when slugs were used instead of ids.
				$shipping_class_term = get_term_by( 'slug', $shipping_class, 'product_shipping_class' );
				$class_cost_string   = $shipping_class_term && $shipping_class_term->term_id ? $this->get_option( 'class_cost_' . $shipping_class_term->term_id, $this->get_option( 'class_cost_' . $shipping_class, '' ) ) : $this->get_option( 'no_class_cost', '' );

				if ( '' === $class_cost_string ) {
					continue;
				}

				$has_costs  = true;
				$class_cost = $this->evaluate_cost(
					$class_cost_string,
					[
						'qty'  => array_sum( wp_list_pluck( $products, 'quantity' ) ),
						'cost' => array_sum( wp_list_pluck( $products, 'line_total' ) ),
					]
				);

				if ( 'class' === $this->type ) {
					$rate['cost'] += $class_cost;
				} else {
					$highest_class_cost = $class_cost > $highest_class_cost ? $class_cost : $highest_class_cost;
				}
			}

			if ( 'order' === $this->type && $highest_class_cost ) {
				$rate['cost'] += $highest_class_cost;
			}
		}

		if ( $met_free_shipping ) {
			$rate['cost'] = 0;
		}

		if ( $has_costs ) {
			$this->add_rate( $rate );
		}

		/**
		 * Allow to filter the shipping rates
		 *
		 * @since 1.0.0
		 *
		 * @param WOOMP_PayNow_Shipping_C2C_711_Frozen $this The shipping method instance.
		 * @param array                                $rate The shipping rate.
		 */
		do_action( 'woocommerce_' . $this->id . '_shipping_add_rate', $this, $rate );
	}

	/**
	 * Evaluate a cost from a sum/string.
	 *
	 * @param  string $sum Sum of shipping.
	 * @param  array  $args Args, must contain `cost` and `qty` keys. Having `array()` as default is for back compat reasons.
	 * @return string
	 */
	protected function evaluate_cost( $sum, $args = [] ) {
		// Add warning for subclasses.
		if ( ! is_array( $args ) || ! array_key_exists( 'qty', $args ) || ! array_key_exists( 'cost', $args ) ) {
			wc_doing_it_wrong( __FUNCTION__, '$args must contain `cost` and `qty` keys.', '4.0.1' );
		}

		include_once WC()->plugin_path() . '/includes/libraries/class-wc-eval-math.php';

		// Allow 3rd parties to process shipping cost arguments.
		$args           = apply_filters( 'woocommerce_evaluate_shipping_cost_args', $args, $sum, $this );
		$locale         = localeconv();
		$decimals       = [ wc_get_price_decimal_separator(), $locale['decimal_point'], $locale['mon_decimal_point'], ',' ];
		$this->fee_cost = $args['cost'];

		// Expand shortcodes.
		add_shortcode( 'fee', [ $this, 'fee' ] );

		$sum = do_shortcode(
			str_replace(
				[
					'[qty]',
					'[cost]',
				],
				[
					$args['qty'],
					$args['cost'],
				],
				$sum
			)
		);

		remove_shortcode( 'fee', [ $this, 'fee' ] );

		// Remove whitespace from string.
		$sum = preg_replace( '/\s+/', '', $sum );

		// Remove locale from string.
		$sum = str_replace( $decimals, '.', $sum );

		// Trim invalid start/end characters.
		$sum = rtrim( ltrim( $sum, "\t\n\r\0\x0B+*/" ), "\t\n\r\0\x0B+-*/" );

		// Do the math.
		return $sum ? WC_Eval_Math::evaluate( $sum ) : 0;
	}

	/**
	 * Work out fee (shortcode).
	 *
	 * @param  array $atts Attributes.
	 * @return string
	 */
	public function fee( $atts ) {
		$atts = shortcode_atts(
			[
				'percent' => '',
				'min_fee' => '',
				'max_fee' => '',
			],
			$atts,
			'fee'
		);

		$calculated_fee = 0;

		if ( $atts['percent'] ) {
			$calculated_fee = $this->fee_cost * ( floatval( $atts['percent'] ) / 100 );
		}

		if ( $atts['min_fee'] && $calculated_fee < $atts['min_fee'] ) {
			$calculated_fee = $atts['min_fee'];
		}

		if ( $atts['max_fee'] && $calculated_fee > $atts['max_fee'] ) {
			$calculated_fee = $atts['max_fee'];
		}

		return $calculated_fee;
	}


	/**
	 * Get items in package.
	 *
	 * @param  array $package Package of items from cart.
	 * @return int
	 */
	public function get_package_item_qty( $package ) {
		$total_quantity = 0;
		foreach ( $package['contents'] as $item_id => $values ) {
			if ( $values['quantity'] > 0 && $values['data']->needs_shipping() ) {
				$total_quantity += $values['quantity'];
			}
		}
		return $total_quantity;
	}


	/**
	 * Finds and returns shipping classes and the products with said class.
	 *
	 * @param mixed $package Package of items from cart.
	 * @return array
	 */
	public function find_shipping_classes( $package ) {
		$found_shipping_classes = [];

		foreach ( $package['contents'] as $item_id => $values ) {
			if ( $values['data']->needs_shipping() ) {
				$found_class = $values['data']->get_shipping_class();

				if ( ! isset( $found_shipping_classes[ $found_class ] ) ) {
					$found_shipping_classes[ $found_class ] = [];
				}

				$found_shipping_classes[ $found_class ][ $item_id ] = $values;
			}
		}

		return $found_shipping_classes;
	}
}
