<?php

use Automattic\WooCommerce\Utilities\NumberUtil;

class RY_ECPay_Shipping_Base extends WC_Shipping_Method {

	public function init() {
		$this->init_settings();

		$this->title              = $this->get_option( 'title' );
		$this->tax_status         = $this->get_option( 'tax_status' );
		$this->cost               = $this->get_option( 'cost' );
		$this->requires           = $this->get_option( 'requires' );
		$this->min_amount         = $this->get_option( 'min_amount', 0 );
		$this->weight_plus_cost   = $this->get_option( 'weight_plus_cost', 0 );
		$this->ignore_discounts   = $this->get_option( 'ignore_discounts', 'no' );
		$this->type               = $this->get_option( 'type', 'class' );
		$this->method_description = $this->get_option( 'description' );

		add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'process_admin_options' ] );

		add_action( 'admin_footer', [ $this, 'enqueue_admin_js' ], 10 );
	}

	public function get_instance_form_fields() {
		return parent::get_instance_form_fields();
	}

	public function is_available( $package ) {
		$is_available = false;

		list($MerchantID, $HashKey, $HashIV, $CVS_type) = RY_ECPay_Shipping::get_ecpay_api_info();
		if ( ! empty( $MerchantID ) && ! empty( $HashKey ) && ! empty( $HashIV ) ) {
			$is_available = true;
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
					if ( 'yes' != $this->get_option( 'class_available_' . $shipping_class_term_id, 'yes' ) ) {
						$is_available = false;
						break;
					}
				}
			}
		}

		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this );
	}

	public function calculate_shipping( $package = [] ) {
		$rate = [
			'id'        => $this->get_rate_id(),
			'label'     => $this->title,
			'cost'      => ( $this->cost ) ? $this->cost : 0,
			'package'   => $package,
			'meta_data' => [
				'no_count' => 1,
			],
		];

		$has_coupon     = $this->check_has_coupon( $this->requires, [ 'coupon', 'either', 'both' ] );
		$has_min_amount = $this->check_has_min_amount( $this->requires, [ 'min_amount', 'either', 'both' ] );

		switch ( $this->requires ) {
			case 'coupon':
				$set_cost_zero = $has_coupon;
				break;
			case 'min_amount':
				$set_cost_zero = $has_min_amount;
				break;
			case 'either':
				$set_cost_zero = $has_min_amount || $has_coupon;
				break;
			case 'both':
				$set_cost_zero = $has_min_amount && $has_coupon;
				break;
			default:
				$set_cost_zero = false;
				break;
		}

		if ( $this->weight_plus_cost > 0 ) {
			$total = WC()->cart->get_cart_contents_weight();
			if ( $total > 0 ) {
				$rate['meta_data']['no_count'] = (int) ceil( $total / $this->weight_plus_cost );
				$rate['cost']                 *= $rate['meta_data']['no_count'];
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

		if ( $set_cost_zero ) {
			$rate['cost'] = 0;
		}

		$this->add_rate( $rate );
		do_action( 'woocommerce_' . $this->id . '_shipping_add_rate', $this, $rate );
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

	protected function check_has_coupon( $requires, $check_requires_list ) {
		if ( in_array( $requires, $check_requires_list ) ) {
			$coupons = WC()->cart->get_coupons();
			if ( $coupons ) {
				foreach ( $coupons as $code => $coupon ) {
					if ( $coupon->is_valid() && $coupon->get_free_shipping() ) {
						return true;
						break;
					}
				}
			}
		}
		return false;
	}

	protected function check_has_min_amount( $requires, $check_requires_list, $original = false ) {
		if ( in_array( $requires, $check_requires_list, true ) ) {
			$total = WC()->cart->get_displayed_subtotal();

			if ( 'no' === $this->ignore_discounts ) {
				$total = $total - WC()->cart->get_discount_total();
				if ( WC()->cart->display_prices_including_tax() ) {
					$total = $total - WC()->cart->get_discount_tax();
				}
			}

			$total = NumberUtil::round( $total, wc_get_price_decimals() );

			if ( $total >= $this->min_amount ) {
				return true;
			}
		}
		return false;
	}

	public function enqueue_admin_js() {
		static $is_print = [];
		if ( is_admin() ) {
			if ( ! isset( $is_print[ $this->id ] ) ) {
				$is_print[ $this->id ] = true;
				wc_enqueue_js(
					'jQuery(function($) {
    function RYECPayShowHide' . $this->id . 'MinAmountField(el) {
        var form = $(el).closest("form");
        var minAmountField = $("#woocommerce_' . $this->id . '_min_amount", form).closest("tr");
        switch( $(el).val() ) {
            case "min_amount":
            case "either":
            case "both":
            case "min_amount_except_discount":
            case "min_amount_except_discount_or_coupon":
            case "min_amount_except_discount_and_coupon":
                minAmountField.show();
                break;
            default:
                minAmountField.hide();
                break;
        }
    }
    $(document.body).on("change", "#woocommerce_' . $this->id . '_requires", function(){
        RYECPayShowHide' . $this->id . 'MinAmountField(this);
    }).change();
    $(document.body).on("wc_backbone_modal_loaded", function(evt, target) {
        if("wc-modal-shipping-method-settings" === target ) {
            RYECPayShowHide' . $this->id . 'MinAmountField($("#wc-backbone-modal-dialog #woocommerce_' . $this->id . '_requires", evt.currentTarget));
        }
    });
});'
				);
			}
		}
	}
}
