<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooMP_Checkout' ) ) {
	final class WooMP_Checkout {

		private static $scripts = array();

		/**
		 * 將購物車&小計置入結帳頁
		 */
		public function set_cart_in_checkout_page() {
			if ( is_wc_endpoint_url( 'order-received' ) ) {
				return;
			}
			echo do_shortcode( '[woocommerce_cart]' ); ?>
			<?php
		}

		/**
		 * 購物車轉跳結帳頁
		 */
		public function redirect_cart_page_to_checkout() {
			if ( is_cart() && WC()->cart->get_cart_contents_count() > 0 ) {
				?>
			<script>
				window.location.href="<?php echo wc_get_checkout_url(); ?>"
			</script>
				<?php
			}
		}

		/**
		 * woocommerce/inluces/class-wc-frontend-scripts.php
		 */
		private static function localize_script( $handle ) {
			if ( ! in_array( $handle, self::$wp_localize_scripts, true ) && wp_script_is( $handle ) ) {
				$data = self::get_script_data( $handle );

				if ( ! $data ) {
					return;
				}

				$name                        = str_replace( '-', '_', $handle ) . '_params';
				self::$wp_localize_scripts[] = $handle;
				wp_localize_script( $handle, $name, apply_filters( $name, $data ) );
			}
		}

		/**
		 * woocommerce/inluces/class-wc-frontend-scripts.php
		 */
		private static function register_script( $handle, $path, $deps = array( 'jquery' ), $version = WC_VERSION, $in_footer = true ) {
			self::$scripts[] = $handle;
			wp_register_script( $handle, $path, $deps, $version, $in_footer );
		}

		/**
		 * woocommerce/inluces/class-wc-frontend-scripts.php
		 */
		private static function enqueue_script( $handle, $path = '', $deps = array( 'jquery' ), $version = WC_VERSION, $in_footer = true ) {
			if ( ! in_array( $handle, self::$scripts, true ) && $path ) {
				self::register_script( $handle, $path, $deps, $version, $in_footer );
			}
			wp_enqueue_script( $handle );
		}

		/**
		 * woocommerce/inluces/class-wc-frontend-scripts.php
		 */
		private static function get_script_data( $handle ) {
			switch ( $handle ) {
				case 'wc-cart':
					$params = array(
						'ajax_url'                     => WC()->ajax_url(),
						'wc_ajax_url'                  => WC_AJAX::get_endpoint( '%%endpoint%%' ),
						'update_shipping_method_nonce' => wp_create_nonce( 'update-shipping-method' ),
						'apply_coupon_nonce'           => wp_create_nonce( 'apply-coupon' ),
						'remove_coupon_nonce'          => wp_create_nonce( 'remove-coupon' ),
					);
					break;
				default:
					$params = false;
			}
		}

		/**
		 * woocommerce/inluces/class-wc-frontend-scripts.php
		 */
		private static function register_scripts() {
			$register_scripts = array(
				'wc-cart' => array(
					'src'     => self::get_asset_url( 'assets/js/frontend/cart' . $suffix . '.js' ),
					'deps'    => array( 'jquery', 'woocommerce', 'wc-country-select', 'wc-address-i18n' ),
					'version' => $version,
				),
			);
		}

		/**
		 * woocommerce/inluces/class-wc-frontend-scripts.php
		 */
		public static function load_scripts() {
			if ( is_checkout() ) {
				self::enqueue_script( 'wc-cart' );
			}
		}

		/**
		 * woocommerce/inluces/class-wc-frontend-scripts.php
		 */
		public static function init() {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'load_scripts' ), 20 );
		}


		/**
		 * 設定取消必填欄位
		 *
		 * @param array $fields Fields.
		 */
		public function set_shipping_field( $fields ) {
			$shipping_method = array(
				'ry_ecpay_shipping_cvs_711',
				'ry_ecpay_shipping_cvs_hilife',
				'ry_ecpay_shipping_cvs_family',
				'ry_newebpay_shipping_cvs',
			);

			foreach ( $shipping_method as $method ) {
				global $woocommerce;
				$chosen_methods  = wc_get_chosen_shipping_method_ids();
				$chosen_shipping = $chosen_methods[0];

				if ( $chosen_shipping == $method ) {
					$fields['billing']['billing_postcode']['required']     = false;
					$fields['billing']['billing_state']['required']        = false;
					$fields['billing']['billing_city']['required']         = false;
					$fields['billing']['billing_address_1']['required']    = false;
					$fields['shipping']['shipping_first_name']['required'] = false;
					$fields['shipping']['shipping_last_name']['required']  = false;
					$fields['shipping']['shipping_phone']['required']      = false;
				}

				/**
				 * 增加運送離島選項
				 */
				if ( $this->has_island_postcodes() ) {
					$fields['billing']['billing_island'] = array(
						'type'  => 'checkbox',
						'label' => '是否運送到離島',
						'class' => array( $this->get_postcodes()[2] ),
						'clear' => true,
					);
				}
			}
			return $fields;
		}

		/**
		 * 修改購買按鈕文字
		 */
		public function custom_button_text( $button_text ) {
			return get_option( ' wc_woomp_setting_place_order_text' );
		}

		/**
		 * 姓名欄位限定要一個以上
		 *
		 * @param array  $fields checkout fields.
		 * @param object $errors error object.
		 */
		public function validate_name_length( $fields, $errors ) {
			// 如果只留下 billing_last_name.
			if ( ! array_key_exists( 'billing_first_name', $fields ) ) {
				if ( mb_strlen( $fields['billing_last_name'], 'utf-8' ) < 2 ) {
					$errors->add( 'validation', '<strong>姓名欄位</strong> 至少兩個字以上' );
				}
			}
			// 如果只留下 billing_first_name.
			if ( ! array_key_exists( 'billing_last_name', $fields ) ) {
				if ( mb_strlen( $fields['billing_first_name'], 'utf-8' ) < 2 ) {
					$errors->add( 'validation', '<strong>姓名欄位</strong> 至少兩個字以上' );
				}
			}
		}

		/**
		 * 取得所有離島郵遞區號
		 */
		public function get_postcodes() {
			global $wpdb;
			$sql              = "SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_locations";
			$post_coded       = $wpdb->get_col( $sql, 2 );
			$island_postcodes = array( 209, 210, 211, 212, 880, 881, 882, 883, 884, 885, 890, 891, 892, 893, 894, 896 );
			$result           = array_intersect( $post_coded, $island_postcodes );
			return $result;
		}

		/**
		 * 檢查是否有設定台灣離島郵遞區號
		 */
		public function has_island_postcodes() {
			if ( count( $this->get_postcodes() ) > 0 ) {
				return true;
			} else {
				return false;
			}
		}
	}

	/**
	 * 指定載入 Woo 客製範本寫外掛主檔案 woomp.php Line 93
	 */

}

$checkout = new WooMP_Checkout();

if ( 'yes' === get_option( 'wc_woomp_setting_replace', 1 ) ) {
	add_action( 'wp_head', array( $checkout, 'redirect_cart_page_to_checkout' ), 1 );
	add_action( 'woocommerce_before_checkout_form', array( $checkout, 'set_cart_in_checkout_page' ) );
	add_filter( 'woocommerce_checkout_fields', array( $checkout, 'set_shipping_field' ), 10000 );
	add_action( 'woocommerce_after_checkout_validation', array( $checkout, 'validate_name_length' ), 10, 2 );
}

if ( ! empty( get_option( ' wc_woomp_setting_place_order_text' ) ) ) {
	add_filter( 'woocommerce_order_button_text', array( $checkout, 'custom_button_text' ), 99, 1 );
}

WooMP_Checkout::init();
