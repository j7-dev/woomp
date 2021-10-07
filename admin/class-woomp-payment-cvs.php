<?php

/**
 * 這支暫時捨棄不用，改以 class-woomp-payment-cod-clone.php 為主
 */

use Automattic\Jetpack\Constants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 確保 Woo 外掛有啟用
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || get_option( 'wc_woomp_setting_cvs_payment', 1 ) === 'no' ) {
	return;
}

add_action( 'plugins_loaded', 'init_woomp_gateway_cvs', 11 );
function init_woomp_gateway_cvs() {
    class WooMP_Payment_CVS extends WC_Payment_Gateway {

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
	  
			$this->id                 = 'woomp_cvs_gateway';
			$this->icon               = apply_filters('woocommerce_offline_icon', '');
			$this->has_fields         = false;
			$this->method_title       = __( '超商取貨付款', 'woomp' );
			$this->method_description = __( '可將商品送到指定的超商門市，取貨時再進行付款。', 'woomp' );
			$this->enable_for_methods = $this->get_option( 'enable_for_methods', array() );
			$this->enable_for_virtual = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes';
		  
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
		  
			// Define user set variables
			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );
			$this->instructions = $this->get_option( 'instructions', $this->description );
		  
			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		  
			// Customer Emails
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
		}
	
	
		/**
		 * Initialize Gateway Settings Form Fields
		 */
		public function init_form_fields() {
	  
			$this->form_fields = apply_filters( 'wc_offline_form_fields', array(
		  
				'enabled' => array(
					'title'   => __( '啓用/停用', 'woomp' ),
					'type'    => 'checkbox',
					'label'   => __( '啟用超商取貨付款', 'woomp' ),
					'default' => 'yes'
				),
				
				'title' => array(
					'title'       => __( 'Title', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'woocommerce' ),
					'default'     => __( '超商取貨付款', 'woomp' ),
					'desc_tip'    => true,
				),
				
				'description' => array(
					'title'       => __( 'Description', 'woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
					'default'     => __( '可將商品送到指定的超商門市，取貨時再進行付款。', 'woomp' ),
					'desc_tip'    => true,
				),
				
				'instructions' => array(
					'title'       => __( 'Instructions', 'woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce' ),
					'default'     => __( '可將商品送到指定的超商門市，取貨時再進行付款。', 'woomp' ),
					'desc_tip'    => true,
				),

				'enable_for_methods' => array(
					'title'             => __( 'Enable for shipping methods', 'woocommerce' ),
					'type'              => 'multiselect',
					'class'             => 'wc-enhanced-select',
					'css'               => 'width: 400px;',
					'default'           => array(
											'ry_ecpay_shipping_cvs_711',
											'ry_ecpay_shipping_cvs_711:3',
											'ry_ecpay_shipping_cvs_hilife',
											'ry_ecpay_shipping_cvs_hilife:4',
											'ry_ecpay_shipping_cvs_family',
											'ry_ecpay_shipping_cvs_family:5',
											'ry_newebpay_shipping_cvs:15'
										),
					'description'       => __( 'If COD is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'woocommerce' ),
					'options'           => $this->load_shipping_method_options(),
					'desc_tip'          => true,
					'custom_attributes' => array(
						'data-placeholder' => __( 'Select shipping methods', 'woocommerce' ),
					),
				),
			) );
		}

		private function load_shipping_method_options() {
			// Since this is expensive, we only want to do it if we're actually on the settings page.
			if ( ! $this->is_accessing_settings() ) {
				return array();
			}

			$data_store = WC_Data_Store::load( 'shipping-zone' );
			$raw_zones  = $data_store->get_zones();

			foreach ( $raw_zones as $raw_zone ) {
				$zones[] = new WC_Shipping_Zone( $raw_zone );
			}

			$zones[] = new WC_Shipping_Zone( 0 );

			$options = array();
			foreach ( WC()->shipping()->load_shipping_methods() as $method ) {

				$options[ $method->get_method_title() ] = array();

				// Translators: %1$s shipping method name.
				$options[ $method->get_method_title() ][ $method->id ] = sprintf( __( 'Any &quot;%1$s&quot; method', 'woocommerce' ), $method->get_method_title() );

				foreach ( $zones as $zone ) {

					$shipping_method_instances = $zone->get_shipping_methods();

					foreach ( $shipping_method_instances as $shipping_method_instance_id => $shipping_method_instance ) {

						if ( $shipping_method_instance->id !== $method->id ) {
							continue;
						}

						$option_id = $shipping_method_instance->get_rate_id();

						// Translators: %1$s shipping method title, %2$s shipping method id.
						$option_instance_title = sprintf( __( '%1$s (#%2$s)', 'woocommerce' ), $shipping_method_instance->get_title(), $shipping_method_instance_id );

						// Translators: %1$s zone name, %2$s shipping method instance name.
						$option_title = sprintf( __( '%1$s &ndash; %2$s', 'woocommerce' ), $zone->get_id() ? $zone->get_zone_name() : __( 'Other locations', 'woocommerce' ), $option_instance_title );

						$options[ $method->get_method_title() ][ $option_id ] = $option_title;
					}
				}
			}

			return $options;
		}

		private function is_accessing_settings() {
			if ( is_admin() ) {
				// phpcs:disable WordPress.Security.NonceVerification
				if ( ! isset( $_REQUEST['section'] ) || 'woomp_cvs_gateway' !== $_REQUEST['section'] ) {
					return false;
				}
				// phpcs:enable WordPress.Security.NonceVerification

				return true;
			}

			if ( Constants::is_true( 'REST_REQUEST' ) ) {
				global $wp;
				if ( isset( $wp->query_vars['rest_route'] ) && false !== strpos( $wp->query_vars['rest_route'], '/payment_gateways' ) ) {
					return true;
				}
			}

			return false;
		}

		private function get_matching_rates( $rate_ids ) {
			// First, match entries in 'method_id:instance_id' format. Then, match entries in 'method_id' format by stripping off the instance ID from the candidates.
			return array_unique( array_merge( array_intersect( $this->enable_for_methods, $rate_ids ), array_intersect( $this->enable_for_methods, array_unique( array_map( 'wc_get_string_before_colon', $rate_ids ) ) ) ) );
		}

		/**
		 * Check If The Gateway Is Available For Use.
		 *
		 * @return bool
		 */
		public function is_available() {
			$order          = null;
			$needs_shipping = false;

			// Test if shipping is needed first.
			if ( WC()->cart && WC()->cart->needs_shipping() ) {
				$needs_shipping = true;
			} elseif ( is_page( wc_get_page_id( 'checkout' ) ) && 0 < get_query_var( 'order-pay' ) ) {
				$order_id = absint( get_query_var( 'order-pay' ) );
				$order    = wc_get_order( $order_id );

				// Test if order needs shipping.
				if ( 0 < count( $order->get_items() ) ) {
					foreach ( $order->get_items() as $item ) {
						$_product = $item->get_product();
						if ( $_product && $_product->needs_shipping() ) {
							$needs_shipping = true;
							break;
						}
					}
				}
			}

			$needs_shipping = apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );

			// Virtual order, with virtual disabled.
			if ( ! $this->enable_for_virtual && ! $needs_shipping ) {
				return false;
			}

			// Only apply if all packages are being shipped via chosen method, or order is virtual.
			if ( ! empty( $this->enable_for_methods ) && $needs_shipping ) {
				$order_shipping_items            = is_object( $order ) ? $order->get_shipping_methods() : false;
				$chosen_shipping_methods_session = WC()->session->get( 'chosen_shipping_methods' );

				if ( $order_shipping_items ) {
					$canonical_rate_ids = $this->get_canonical_order_shipping_item_rate_ids( $order_shipping_items );
				} else {
					$canonical_rate_ids = $this->get_canonical_package_rate_ids( $chosen_shipping_methods_session );
				}

				if ( ! count( $this->get_matching_rates( $canonical_rate_ids ) ) ) {
					return false;
				}
			}

			return parent::is_available();
		}

		private function get_canonical_package_rate_ids( $chosen_package_rate_ids ) {

			$shipping_packages  = WC()->shipping()->get_packages();
			$canonical_rate_ids = array();

			if ( ! empty( $chosen_package_rate_ids ) && is_array( $chosen_package_rate_ids ) ) {
				foreach ( $chosen_package_rate_ids as $package_key => $chosen_package_rate_id ) {
					if ( ! empty( $shipping_packages[ $package_key ]['rates'][ $chosen_package_rate_id ] ) ) {
						$chosen_rate          = $shipping_packages[ $package_key ]['rates'][ $chosen_package_rate_id ];
						$canonical_rate_ids[] = $chosen_rate->get_method_id() . ':' . $chosen_rate->get_instance_id();
					}
				}
			}

			return $canonical_rate_ids;
		}

		private function get_canonical_order_shipping_item_rate_ids( $order_shipping_items ) {

			$canonical_rate_ids = array();

			foreach ( $order_shipping_items as $order_shipping_item ) {
				$canonical_rate_ids[] = $order_shipping_item->get_method_id() . ':' . $order_shipping_item->get_instance_id();
			}

			return $canonical_rate_ids;
		}
	
	
		/**
		 * Output for the order received page.
		 */
		public function thankyou_page() {
			if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) );
			}
		}
	
	
		/**
		 * Add content to the WC emails.
		 *
		 * @access public
		 * @param WC_Order $order
		 * @param bool $sent_to_admin
		 * @param bool $plain_text
		 */
		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		
			//if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'pending' ) ) {
			//}
			echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
		}
	
	
		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment( $order_id ) {
	
			$order = wc_get_order( $order_id );
			
			// Mark as on-hold (we're awaiting the payment)
			$order->update_status( 'pending', __( 'Awaiting offline payment', 'woocommerce' ) );
			
			// Reduce stock levels
			$order->reduce_order_stock();
			
			// Remove cart
			WC()->cart->empty_cart();
			
			// Return thankyou redirect
			return array(
				'result' 	=> 'success',
				'redirect'	=> $this->get_return_url( $order )
			);
		}
	
  	}
}

add_filter( 'woocommerce_payment_gateways', 'woomp_cvs_add_to_gateways' );
function woomp_cvs_add_to_gateways( $gateways ) {
	$gateways[] = 'WooMP_Payment_CVS';
	return $gateways;
}

add_filter( 'plugin_action_links_woomp', 'woomp_cvs_gateway_plugin_links' );
function woomp_cvs_gateway_plugin_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=woomp_cvs_gateway' ) . '">' . __( '設定', 'woomp' ) . '</a>'
	);
	return array_merge( $plugin_links, $links );
}