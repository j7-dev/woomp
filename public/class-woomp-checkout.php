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
				'paynow_shipping_c2c_711',
				'paynow_shipping_c2c_family',
				'paynow_shipping_c2c_hilife',
				'ry_smilepay_shipping_cvs_711',
				'ry_smilepay_shipping_cvs_fami',
			);

			foreach ( $shipping_method as $method ) {
				global $woocommerce;
				$chosen_methods = wc_get_chosen_shipping_method_ids();
				if ( count( $chosen_methods ) > 0 ) {

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
				}

				if ( $this->is_virtual_cart() || $this->is_free_cart() ) {
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
				if ( $this->has_island_postcodes() && ! $this->is_virtual_cart() ) {
					$fields['billing']['billing_island'] = array(
						'type'  => 'checkbox',
						'label' => '寄送到離島區域',
						'class' => array( $this->get_postcodes()[2] ),
						'clear' => true,
					);
				}
			}
			return $fields;
		}

		/**
		 * 新增沒送到的離島縣市欄位
		 *
		 * @param array $checkout Checkout Fields.
		 */
		public function set_checkout_field( $checkout ) {
			woocommerce_form_field(
				'billing_island_none',
				array(
					'type'    => 'text',
					'label'   => '沒送到的離島縣市',
					'default' => implode( ',', $this->get_island_hide() ),
				),
			);
		}

		/**
		 * 修改購買按鈕文字
		 */
		public function custom_button_text( $button_text ) {
			return get_option( ' wc_woomp_setting_place_order_text' );
		}

		/**
		 * 結帳欄位格式驗證
		 *
		 * @param array  $fields checkout fields.
		 * @param object $errors error object.
		 */
		public function field_validate( $fields, $errors ) {
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

			// 在沒有勾選運送到離島的狀況下選擇離島超商取貨.
			if ( array_key_exists( 'CVSAddress', $fields ) ) {
				if ( 1 !== $fields['billing_postcode'] && ! empty( $fields['CVSAddress'] ) ) {
					if ( strpos( $fields['CVSAddress'], '金門縣' ) > -1 || strpos( $fields['CVSAddress'], '澎湖縣' ) > -1 || strpos( $fields['CVSAddress'], '連江縣' ) > -1 ) {
						$errors->add( 'validation', '<strong>外島超商</strong> 您選擇的運送方式不在運送範圍內' );
					}
				}
			}

			// 電話位數一定要 10 碼.
			if ( array_key_exists( 'billing_phone', $fields ) ) {
				if ( mb_strlen( $fields['billing_phone'], 'utf-8' ) !== 10 ) {
					$errors->add( 'validation', '<strong>聯絡電話</strong> 長度有誤，必須為 10 碼' );
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

		/**
		 * 取得沒有被勾選的外島縣市
		 */
		public function get_island_hide() {
			global $wpdb;

			$island_hide      = array();
			$island_kinmen    = array( 890, 891, 892, 893, 894, 896 );
			$island_penghu    = array( 880, 881, 882, 883, 884, 885 );
			$island_lianjiang = array( 209, 210, 211, 212 );

			$sql        = "SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_locations";
			$post_coded = $wpdb->get_col( $sql, 2 );

			if ( ! array_intersect( $post_coded, $island_kinmen ) ) {
				$island_hide[] = '金門縣';
			}

			if ( ! array_intersect( $post_coded, $island_penghu ) ) {
				$island_hide[] = '澎湖縣';
			}

			if ( ! array_intersect( $post_coded, $island_lianjiang ) ) {
				$island_hide[] = '連江縣';
			}

			return $island_hide;
		}

		/**
		 * 檢查購物車是否只有一個虛擬商品
		 */
		private function is_virtual_cart() {
			global $woocommerce;
			$virtual_products = 0;
			$products         = $woocommerce->cart->get_cart();
			foreach ( $products as $product ) {
				if ( 'yes' === get_post_meta( $product['product_id'], '_virtual', true ) || 'yes' === get_post_meta( $product['variation_id'], '_virtual', true ) ) {
					++$virtual_products;
				}
			}

			if ( 1 === $virtual_products && 1 === count( $products ) ) {
				return true;
			}

			return false;
		}

		/**
		 * 檢查購物車總金額為 0
		 */
		private function is_free_cart() {
			global $woocommerce;
			$total = $woocommerce->cart->cart_contents_total;
			if ( '0' === $total ) {
				return true;
			}
			return false;
		}

	}

	/**
	 * 指定��入 Woo 客製範本寫外掛主檔案 woomp.php Line 93
	 */

}

$checkout = new WooMP_Checkout();

if ( 'yes' === get_option( 'wc_woomp_setting_replace', 1 ) ) {
	add_action( 'wp_head', array( $checkout, 'redirect_cart_page_to_checkout' ), 1 );
	add_action( 'woocommerce_before_checkout_form', array( $checkout, 'set_cart_in_checkout_page' ) );
	add_filter( 'woocommerce_checkout_fields', array( $checkout, 'set_shipping_field' ), 10000 );
	add_action( 'woocommerce_after_checkout_validation', array( $checkout, 'field_validate' ), 10, 2 );
	add_action( 'woocommerce_after_order_notes', array( $checkout, 'set_checkout_field' ) );
}

if ( ! empty( get_option( ' wc_woomp_setting_place_order_text' ) ) ) {
	add_filter( 'woocommerce_order_button_text', array( $checkout, 'custom_button_text' ), 99, 1 );
}

WooMP_Checkout::init();
