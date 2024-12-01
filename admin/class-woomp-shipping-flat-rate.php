<?php
/**
 * WooMP Shipping Flat Rate
 *
 * 複寫 WooCommerce 單一費率的設定項以增加免運費功能
 *
 * @package WooMP
 */

namespace WooMP\Shipping;

use Automattic\WooCommerce\Utilities\NumberUtil;

if ( ! class_exists( 'WC_Shipping_Flat_Rate' ) ) {
	return;
}

/**
 * WooMP Shipping Flat Rate Class
 *
 * 擴展 WooCommerce 單一運費方式,增加免運費相關設定
 */
class WooMP_Shipping_Flat_Rate extends \WC_Shipping_Flat_Rate {

	/**
	 * 最低訂單金額
	 *
	 * @var float
	 */
	protected $min_amount = 0;

	/**
	 * 免運費需求條件
	 *
	 * @var string
	 */
	protected $cost_requires = '';

	/**
	 * 是否忽略折扣
	 *
	 * @var string
	 */
	protected $ignore_discounts = 'no';

	/**
	 * 每增加重量單位的倍數運費
	 *
	 * @var float
	 */
	protected $weight_plus_cost = 0;

	/**
	 * 初始化運費設定
	 *
	 * @return void
	 */
	public function init() {
		$cost_desc = __( 'Enter a cost (excl. tax) or sum, e.g. <code>10.00 * [qty]</code>.', 'woocommerce' ) . '<br/><br/>' . __( 'Use <code>[qty]</code> for the number of items, <br/><code>[cost]</code> for the total cost of items, and <code>[fee percent="10" min_fee="20" max_fee=""]</code> for percentage based fees.', 'woocommerce' );

		$settings = [
			'title'            => [
				'title'       => __( 'Method title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'Flat rate', 'woocommerce' ),
				'desc_tip'    => true,
			],
			'tax_status'       => [
				'title'   => __( 'Tax status', 'woocommerce' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'taxable',
				'options' => [
					'taxable' => __( 'Taxable', 'woocommerce' ),
					'none'    => _x( 'None', 'Tax status', 'woocommerce' ),
				],
			],
			'cost'             => [
				'title'             => __( 'Cost', 'woocommerce' ),
				'type'              => 'text',
				'placeholder'       => '',
				'description'       => $cost_desc,
				'default'           => '0',
				'desc_tip'          => true,
				'sanitize_callback' => [ $this, 'sanitize_cost' ],
			],
			'cost_requires'    => [
				'title'   => __( 'Free shipping requires...', 'woocommerce' ),
				'type'    => 'select',
				'default' => '',
				'options' => [
					''                      => __( 'N/A', 'woocommerce' ),
					'coupon'                => __( 'A valid free shipping coupon', 'woocommerce' ),
					'min_amount'            => __( 'A minimum order amount', 'woocommerce' ),
					'min_amount_or_coupon'  => __( 'A minimum order amount OR a coupon', 'woocommerce' ),
					'min_amount_and_coupon' => __( 'A minimum order amount AND a coupon', 'woocommerce' ),
				],
				'class'   => 'wc-enhanced-select',
			],
			'min_amount'       => [
				'title'       => __( 'Minimum order amount', 'ry-woocommerce-tools' ),
				'type'        => 'price',
				'default'     => 0,
				'placeholder' => wc_format_localized_price( 0 ),
				'description' => __( 'Users will need to spend this amount to get free shipping (if enabled above).', 'woocommerce' ),
				'desc_tip'    => true,
			],
			'ignore_discounts' => [
				'title'       => __( 'Coupons discounts', 'woocommerce' ),
				'label'       => __( 'Apply minimum order rule before coupon discount', 'woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'If checked, free shipping would be available based on pre-discount order amount.', 'woocommerce' ),
				'default'     => 'no',
				'desc_tip'    => true,
			],
			'weight_plus_cost' => [
				// translators: %s WooCommerce weight unit
				'title'       => sprintf( __( 'Every weight (%s) to plus times of cost', 'ry-woocommerce-tools' ), __( get_option( 'woocommerce_weight_unit' ), 'woocommerce' ) ),
				'type'        => 'number',
				'default'     => 0,
				'placeholder' => 0,
				'description' => __( 'Calculate free shipping first. 0 to disable plus cost by weight.', 'ry-woocommerce-tools' ),
				'desc_tip'    => true,
			],
		];

		$shipping_classes = WC()->shipping()->get_shipping_classes();

		if ( ! empty( $shipping_classes ) ) {
			$settings['class_costs'] = [
				'title'       => __( 'Shipping class costs', 'woocommerce' ),
				'type'        => 'title',
				'default'     => '',
				/* translators: %s: URL for link. */
				'description' => sprintf( __( 'These costs can optionally be added based on the <a href="%s">product shipping class</a>.', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=shipping&section=classes' ) ),
			];
			foreach ( $shipping_classes as $shipping_class ) {
				if ( ! isset( $shipping_class->term_id ) ) {
					continue;
				}
				$settings[ 'class_cost_' . $shipping_class->term_id ] = [
					/* translators: %s: shipping class name */
					'title'             => sprintf( __( '"%s" shipping class cost', 'woocommerce' ), esc_html( $shipping_class->name ) ),
					'type'              => 'text',
					'placeholder'       => __( 'N/A', 'woocommerce' ),
					'description'       => $cost_desc,
					'default'           => $this->get_option( 'class_cost_' . $shipping_class->slug ), // Before 2.5.0, we used slug here which caused issues with long setting names.
					'desc_tip'          => true,
					'sanitize_callback' => [ $this, 'sanitize_cost' ],
				];
			}

			$settings['no_class_cost'] = [
				'title'             => __( 'No shipping class cost', 'woocommerce' ),
				'type'              => 'text',
				'placeholder'       => __( 'N/A', 'woocommerce' ),
				'description'       => $cost_desc,
				'default'           => '',
				'desc_tip'          => true,
				'sanitize_callback' => [ $this, 'sanitize_cost' ],
			];

			$settings['type'] = [
				'title'   => __( 'Calculation type', 'woocommerce' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'default' => 'class',
				'options' => [
					'class' => __( 'Per class: Charge shipping for each shipping class individually', 'woocommerce' ),
					'order' => __( 'Per order: Charge shipping for the most expensive shipping class', 'woocommerce' ),
				],
			];
		}

		$this->instance_form_fields = $settings;
		$this->title                = $this->get_option( 'title' );
		$this->tax_status           = $this->get_option( 'tax_status' );
		$this->cost                 = $this->get_option( 'cost' );
		$this->type                 = $this->get_option( 'type', 'class' );
		$this->min_amount           = $this->get_option( 'min_amount', 0 );
		$this->cost_requires        = $this->get_option( 'cost_requires' );
		$this->ignore_discounts     = $this->get_option( 'ignore_discounts' );
		$this->weight_plus_cost     = $this->get_option( 'weight_plus_cost', 0 );

		add_action( 'admin_footer', [ $this, 'enqueue_admin_js' ], 10 );
	}

	/**
	 * 檢查運送方式是否可用
	 *
	 * @param array $package 運送包裹資訊.
	 * @return bool
	 */
	public function is_available( $package ) {
		$has_coupon         = false;
		$has_met_min_amount = false;

		if ( in_array( $this->requires, [ 'coupon', 'either', 'both' ], true ) ) {
			$coupons = WC()->cart->get_coupons();

			if ( $coupons ) {
				foreach ( $coupons as $code => $coupon ) {
					if ( $coupon->is_valid() && $coupon->get_free_shipping() ) {
						$has_coupon = true;
						break;
					}
				}
			}
		}

		if ( in_array( $this->requires, [ 'min_amount', 'either', 'both' ], true ) ) {
			$total = WC()->cart->get_displayed_subtotal();

			if ( WC()->cart->display_prices_including_tax() ) {
				$total = $total - WC()->cart->get_discount_tax();
			}

			if ( 'no' === $this->ignore_discounts ) {
				$total = $total - WC()->cart->get_discount_total();
			}

			$total = NumberUtil::round( $total, wc_get_price_decimals() );

			if ( $total >= $this->min_amount ) {
				$has_met_min_amount = true;
			}
		}

		switch ( $this->requires ) {
			case 'min_amount':
				$is_available = $has_met_min_amount;
				break;
			case 'coupon':
				$is_available = $has_coupon;
				break;
			case 'both':
				$is_available = $has_met_min_amount && $has_coupon;
				break;
			case 'either':
				$is_available = $has_met_min_amount || $has_coupon;
				break;
			default:
				$is_available = true;
				break;
		}

		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this );
	}

	/**
	 * 計算運費金額
	 *
	 * @param array $package 運送包裹資訊.
	 * @return void
	 */
	public function calculate_shipping( $package = [] ) {
		$rate = [
			'id'        => $this->get_rate_id(),
			'label'     => $this->title,
			'cost'      => (float) $this->cost,
			'package'   => $package,
			'meta_data' => [
				'no_count' => 1,
			],
		];

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
					$rate['cost'] += (float) $class_cost;
				} else {
					$highest_class_cost = $class_cost > $highest_class_cost ? $class_cost : $highest_class_cost;
				}
			}

			if ( 'order' === $this->type && $highest_class_cost ) {
				$rate['cost'] += (float) $highest_class_cost;
			}
		}

		$has_coupon     = $this->check_has_coupon( $this->cost_requires, [ 'coupon', 'min_amount_or_coupon', 'min_amount_and_coupon' ] );
		$has_min_amount = $this->check_has_min_amount( $this->cost_requires, [ 'min_amount', 'min_amount_or_coupon', 'min_amount_and_coupon' ] );

		switch ( $this->cost_requires ) {
			case 'coupon':
				$set_cost_zero = $has_coupon;
				break;
			case 'min_amount':
				$set_cost_zero = $has_min_amount;
				break;
			case 'min_amount_or_coupon':
				$set_cost_zero = $has_min_amount || $has_coupon;
				break;
			case 'min_amount_and_coupon':
				$set_cost_zero = $has_min_amount && $has_coupon;
				break;
			default:
				$set_cost_zero = false;
				break;
		}

		if ( $set_cost_zero ) {
			$rate['cost'] = 0;
		}

		if ( $this->weight_plus_cost > 0 ) {
			$total = WC()->cart->get_cart_contents_weight();
			if ( $total > 0 ) {
				$rate['meta_data']['no_count'] = (int) ceil( $total / $this->weight_plus_cost );
				$rate['cost']                 *= $rate['meta_data']['no_count'];
			}
		}

		$this->add_rate( $rate );
		do_action( 'woocommerce_' . $this->id . '_shipping_add_rate', $this, $rate );
	}

	/**
	 * 檢查是否有符合條件的優惠券
	 *
	 * @param string $requires 需求條件.
	 * @param array  $check_requires_list 需檢查的條件清單.
	 * @return bool
	 */
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

	/**
	 * 檢查是否達到最低訂單金額
	 *
	 * @param string $requires 需求條件.
	 * @param array  $check_requires_list 需檢查的條件清單.
	 * @param bool   $original 是否使用原始金額.
	 * @return bool
	 */
	protected function check_has_min_amount( $requires, $check_requires_list, $original = false ) {
		if ( in_array( $requires, $check_requires_list ) ) {
			$total = WC()->cart->get_displayed_subtotal();
			if ( $original === false ) {
				if ( 'incl' === WC()->cart->get_tax_price_display_mode() ) {
					$total = round( $total - ( WC()->cart->get_cart_discount_total() + WC()->cart->get_cart_discount_tax_total() ), wc_get_price_decimals() );
				} else {
					$total = round( $total - WC()->cart->get_cart_discount_total(), wc_get_price_decimals() );
				}
			}
			if ( $total >= $this->min_amount ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 在管理後台加入 JavaScript 處理
	 *
	 * @return void
	 */
	public static function enqueue_admin_js() {
		wc_enqueue_js(
			"jQuery( function( $ ) {
				function woompFlatShippingShowHideMinAmountField( el ) {
					var form = $( el ).closest( 'form' );
					var minAmountField = $( '#woocommerce_flat_rate_min_amount', form ).closest( 'tr' );
					var ignoreDiscountField = $( '#woocommerce_flat_rate_ignore_discounts', form ).closest( 'tr' );
					if ( 'coupon' === $( el ).val() || '' === $( el ).val() ) {
						minAmountField.hide();
						ignoreDiscountField.hide();
					} else {
						minAmountField.show();
						ignoreDiscountField.show();
					}
				}

				$( document.body ).on( 'change', '#woocommerce_flat_rate_cost_requires', function() {
					woompFlatShippingShowHideMinAmountField( this );
				});

				// Change while load.
				$( '#woocommerce_flat_rate_cost_requires' ).trigger( 'change' );
				$( document.body ).on( 'wc_backbone_modal_loaded', function( evt, target ) {
					if ( 'wc-modal-shipping-method-settings' === target ) {
						woompFlatShippingShowHideMinAmountField( $( '#wc-backbone-modal-dialog #woocommerce_flat_rate_cost_requires', evt.currentTarget ) );
					}
				} );
			});"
		);
	}
}
