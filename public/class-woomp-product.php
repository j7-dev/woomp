<?php

/**
 * 前台訂單相關功能
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooMP_Product_Public' ) ) {
	class WooMP_Product_Public {
		/**
		 * 初始化
		 */
		public static function init() {
			$class = new self();
			add_action( 'woocommerce_after_single_product', array( $class, 'display_cart' ) );
		}

		public function display_cart() {
			global $post;

			if ( 'no' === get_post_meta( $post->ID, '_onepagecheckout', true ) ) {
				return;
			}

			$html  = '<div class="product"><div class="product-entry-wrapper">';
			$html .= do_shortcode( '[woocommerce_checkout]' );
			$html .= '</div></div>';
			echo $html;
		}
	}
	//WooMP_Product_Public::init();
}
