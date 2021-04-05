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
				wp_safe_redirect( wc_get_checkout_url() );
				exit;
			}
		}

		/**
		 * 台灣縣市下拉選單
		 */
		public function set_tw_zipcode() {
			?>
			<script>
				jQuery(function($) {
					function updateValue( field ){
						$("#"+field+"_state").val($(".woocommerce-"+field+"-fields select[name=\'county\']").val());
						$("#"+field+"_city").val($(".woocommerce-"+field+"-fields select[name=\'district\']").val());
						$("#"+field+"_postcode").val($(".woocommerce-"+field+"-fields input[name=\'zipcode\']").val());
					}
					function updateField(field){
						$(".woocommerce-"+field+"-fields select[name=\'county\']").appendTo($("#"+field+"_state_field"));
						$(".woocommerce-"+field+"-fields select[name=\'district\']").appendTo($("#"+field+"_city_field"));
						$(".woocommerce-"+field+"-fields input[name=\'zipcode\']").appendTo($("#"+field+"_postcode_field"));
					} 
					$(document).ready(function($){
						if( $('#billing_country').val() === 'TW' ){
							
							$(".woocommerce-billing-fields,.woocommerce-shipping-fields").twzipcode();
							
							updateField("billing");
							updateField("shipping");
							
							$("select[name=\'county\'],select[name=\'district\']").change(function(){
								updateValue("billing");
								updateValue("shipping");
							})
	
							$("input[name=\'zipcode\']").keyup(function(){
								updateValue("billing");
								updateValue("shipping");
							})
	
							$("#billing_postcode,#billing_state,#billing_city,#shipping_postcode,#shipping_state,#shipping_city").hide();
						}
	
						$('#billing_country').on('change',function(){
							if($(this).val() === 'TW'){
								$(".woocommerce-billing-fields,.woocommerce-shipping-fields").twzipcode();
								updateField("billing");
								updateField("shipping");
								$("select[name=\'county\'],select[name=\'district\'],input[name=\'zipcode\']").show();
								$("select[name=\'county\'],select[name=\'district\']").change(function(){
									updateValue("billing");
									updateValue("shipping");
								})
								$("input[name=\'zipcode\']").keyup(function(){updateValue();})
								$("#billing_postcode,#billing_state,#billing_city,#shipping_postcode,#shipping_state,#shipping_city").hide();
								$("select#billing_state + span, select#shipping_state + span").hide();
							} else {
								$("form.woocommerce-checkout").twzipcode('destory');
								$("select[name=\'county\'],select[name=\'district\'],input[name=\'zipcode\']").hide();
								$("#billing_postcode,#billing_state,#billing_city,#shipping_postcode,#shipping_state,#shipping_city").show();
								$("select#billing_state + span, select#shipping_state + span").show();
							}
						})
	
						// 同步帳單資訊與超商欄位資訊
						$('[name="billing_first_name"]').on('change',function (e) { 
							$('#shipping_first_name').val($(this).val())
						});
						$('[name="billing_last_name"]').on('change',function (e) { 
							$('#shipping_last_name').val($(this).val())
						});
						$('[name="billing_phone"]').on('change',function (e) { 
							$('#shipping_phone').val($(this).val())
						});
					})
				})
			</script>
			<?php
		}

		/**
		 * 國家欄位移到物流選擇上面
		 */
		public function set_country_to_top(){
			if ( get_option( 'wc_woomp_setting_billing_country_pos', 1 ) === 'yes' ){ ?>
				<script>
					jQuery(function($){
						$('#billing_country_field').prependTo('#order_review');
						$('#billing_country_field').css('margin-bottom','25px');
					})
				</script>
			<?php
			}
		}

		/**
		 * 更改購物車數量時自動更新金額
		 */
		public function set_quantity_update_cart() {
			?>
			<script>
			jQuery(function($){
				$(document).ready(function($){
					$('.woocommerce-cart-form').attr('action','<?php echo wc_get_checkout_url(); ?>')
					$('.woocommerce').on('change', 'input.qty', function(){
					setTimeout(() => {
						$("[name='update_cart']").trigger("click");
					}, 1000);
					});
				})
			})
			</script>
			<?php
		}

		/**
		 * 移動結帳按鈕&金流位置
		 */
		public function set_place_button_position() {
			?>
		  	<script>  
			jQuery(function($){
				function placeCheckoutButton(){
					if(jQuery('#paymentWrap').length === 0){
						jQuery('table.shop_table.woocommerce-checkout-review-order-table').after('<table id="paymentWrap" style="margin-top: -24px; margin-bottom: -20px;"><tr><th style="width: 159px; font-weight: normal; padding: 0; font-size: 14px; text-align: left;">付款方式</th><td></td></tr></table>');
						jQuery('#payment').appendTo(jQuery('#paymentWrap td'))
					} else {
						jQuery( '#payment .woocommerce-terms-and-conditions-wrapper' ).remove(); // 移除重複的隱私權政策
					}
					if(jQuery('#placeOrderWrap').length === 0){
						jQuery('form.woocommerce-checkout #customer_details').append('<div id="placeOrderWrap"></div>')
						jQuery('.woocommerce-terms-and-conditions-wrapper,#place_order').appendTo(jQuery('#placeOrderWrap'));
					}
					jQuery('#paymentWrap #payment button').remove()
				}

				jQuery(document.body).on('updated_checkout', function (e, data) {
					placeCheckoutButton();
				})
			})    
			</script>
			<?php
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
		 * 根據運送方式顯示資訊
		 */
		public function set_shipping_info() {
			?>
			<script>
			jQuery(function($){
				$(document.body).on('updated_checkout', function (e, data) {
	
					/**
					 * 針對物流方式顯示帳單與運送地址欄位
					 */
					if( $('#shipping_method li').length > 1 ){
						if( 
							$('#shipping_method li input:checked').val() === "ecpay_shipping" || 
							$('#shipping_method li input:checked').val().includes('ry_ecpay_shipping_cvs') ||
							$('#shipping_method li input:checked').val().includes('ry_newebpay_shipping_cvs') 
						){
							$('.woocommerce-shipping-fields').hide()
							$('h3#ship-to-different-address input').hide()
							$('#billing_address_1_field').hide();
							$('#billing_address_2_field').hide();
							$('#billing_city_field').hide();
							$('#billing_state_field').hide();
							$('#billing_postcode_field').hide();
						} else {
							$('.woocommerce-shipping-fields').show()
							$('h3#ship-to-different-address input').show()
							$('#billing_address_1_field').show();
							$('#billing_address_2_field').show();
							$('#billing_city_field').show();
							$('#billing_state_field').show();
							$('#billing_postcode_field').show();
						}
					}
				});
			})
			</script>
			<?php
		}

		/**
		 * 修改購買按鈕文字
		 */
		public function custom_button_text( $button_text ) {
			return get_option( ' wc_woomp_setting_place_order_text' );
		}
	}

	/**
	 * 指定載入 Woo 客製範本寫外掛主檔案 woomp.php Line 93
	 */

}

$checkout = new WooMP_Checkout();

if( 'yes' === get_option( 'wc_woomp_setting_replace', 1 ) ){
	add_action( 'wp_head', array( $checkout, 'redirect_cart_page_to_checkout' ), 5 );
	add_action( 'woocommerce_before_checkout_form', array( $checkout, 'set_cart_in_checkout_page' ) );
	add_filter( 'woocommerce_after_checkout_form', array( $checkout, 'set_quantity_update_cart' ) );
	add_filter( 'woocommerce_after_checkout_form', array( $checkout, 'set_place_button_position' ) );
	add_filter( 'woocommerce_after_checkout_form', array( $checkout, 'set_shipping_info' ) );
}

if( 'yes' === get_option( 'wc_woomp_setting_tw_address', 1 ) ){
	add_filter( 'woocommerce_after_checkout_form', array( $checkout, 'set_tw_zipcode' ) );
}

if( 'yes' === get_option( 'wc_woomp_setting_billing_country_pos', 1 ) ){
	add_filter( 'woocommerce_after_checkout_form', array( $checkout, 'set_country_to_top' ) );
}

if( !empty( get_option( ' wc_woomp_setting_place_order_text' ) ) ) {
	add_filter( 'woocommerce_order_button_text', array( $checkout, 'custom_button_text' ), 99, 1 );
}

WooMP_Checkout::init();
