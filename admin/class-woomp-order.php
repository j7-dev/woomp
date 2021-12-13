<?php

/**
 * 訂單相關功能
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WooMP_Order' ) ) {
	class WooMP_Order {
		/**
		 * 初始化
		 */
		public static function init() {
			$class = new self();
			add_action( 'init', array( $class, 'add_order_status_in_transit' ) );
			add_action( 'wc_order_statuses', array( $class, 'add_order_statuses' ) );
			add_filter( 'woocommerce_reports_order_statuses', array( $class, 'add_order_statuses' ) );
			add_filter( 'woocommerce_order_is_paid_statuses', array( $class, 'add_report_paid_statuses' ) );
			add_filter( 'wp_ajax_delete_shipping_ecpay_cvs', array( $class, 'delete_shipping_ecpay_cvs' ) );

			add_filter( 'manage_shop_order_posts_columns', array( $class, 'shop_order_columns' ), 11, 1 );
			add_action( 'manage_shop_order_posts_custom_column', array( $class, 'shop_order_column' ), 11, 2 );

			add_filter( 'bulk_actions-edit-shop_order', array( $class, 'bulk_action' ), 99, 1 );
			add_filter( 'handle_bulk_actions-edit-shop_order', array( $class, 'print_shipping_note' ), 10, 3 );
		}

		/**
		 * 增加訂單狀態 - 配送中
		 */
		public function add_order_status_in_transit() {

			register_post_status(
				'wc-wmp-in-transit',
				array(
					'label'                     => '配送中',
					'public'                    => true,
					'show_in_admin_status_list' => true,
					'show_in_admin_all_list'    => true,
					'exclude_from_search'       => false,
				)
			);
		}

		public function add_order_statuses( $order_statuses ) {
			$order_statuses['wc-wmp-in-transit'] = '配送中';
			return $order_statuses;
		}

		public function add_report_paid_statuses( $statues ) {
			$statues[] = 'wc-wmp-in-transit';
			return $statues;
		}

		/**
		 * 訂單管理頁刪除綠界物流資訊 Ajax
		 */
		public function delete_shipping_ecpay_cvs() {
			if ( isset( $_POST['ecpayShippingId'] ) && isset( $_POST['orderId'] ) ) {
				$order_id            = $_POST['orderId'];
				$ecpay_shipping_id   = $_POST['ecpayShippingId'];
				$ecpay_shipping_info = get_post_meta( $order_id, '_ecpay_shipping_info', true );

				unset( $ecpay_shipping_info[ $ecpay_shipping_id ] );
				update_post_meta( $order_id, '_ecpay_shipping_info', $ecpay_shipping_info );

				echo wp_json_encode( '已刪除' );
			} else {
				echo wp_json_encode( '發生錯誤' );
			}
			die();
		}

		/**
		 * 後台訂單列表增加單號欄位
		 */
		public function shop_order_columns( $columns ) {
			$add_index = array_search( 'shipping_address', array_keys( $columns ) ) + 1;
			$pre_array = array_splice( $columns, 0, $add_index );
			$array     = array(
				'wmp_payment_no'  => __( '金流單號', 'ry-woocommerce-tools' ),
				'wmp_shipping_no' => __( '物流單號', 'ry-woocommerce-tools' ),
			);
			return array_merge( $pre_array, $array, $columns );
		}

		/**
		 * 後台訂單列表增加單號欄位
		 */
		public function shop_order_column( $column, $post_id ) {
			if ( $column == 'wmp_payment_no' ) {
				$order = wc_get_order( $post_id );
				echo $order->get_data()['transaction_id'];
				// echo ( ! empty( $trans_id ) ) ? esc_html( $trans_id ) : '';
			}
			if ( $column == 'wmp_shipping_no' ) {
				global $the_order;
				$shipping_list = $the_order->get_meta( '_ecpay_shipping_info', true );
				if ( is_array( $shipping_list ) ) {
					foreach ( $shipping_list as $item ) {
						if ( $item['LogisticsType'] == 'CVS' ) {
							echo $item['PaymentNo'] . ' ' . $item['ValidationNo'];
						}
					}
				} else {
					?>
					<div class="shippingNoWrap">
						<input type="text" name="shippingNo" placeholder="請輸入物流單號" value="<?php echo ( get_post_meta( $post_id, 'wmp_shipping_no', true ) ) ? get_post_meta( $post_id, 'wmp_shipping_no', true ) : ''; ?>" style="width: 100%;" maxlength=100>
						<input type="hidden" class="orderId" value="<?php echo esc_attr( $post_id ); ?>">
						<div class="shipping-no-loading">
							<div class="lds-spinner"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>
						</div>
					</div>
					<?php
				}
			}
		}

		/**
		 * 訂單列表增加列運托運單批次操作選單
		 */
		public function bulk_action( $actions ) {
			switch ( RY_WT::get_option( 'ecpay_shipping_cvs_type' ) ) {
				case 'B2C':
					$actions['ry_print_ecpay_cvs_711']    = __( 'Print ECPay shipping booking note (711)', 'woomp' );
					$actions['ry_print_ecpay_cvs_family'] = __( 'Print ECPay shipping booking note (family)', 'woomp' );
					$actions['ry_print_ecpay_cvs_hilife'] = __( 'Print ECPay shipping booking note (hilife)', 'woomp' );
					break;
				case 'C2C':
					$actions['ry_print_ecpay_cvs_711']    = __( 'Print ECPay shipping booking note (711)', 'woomp' );
					$actions['ry_print_ecpay_cvs_family'] = __( 'Print ECPay shipping booking note (family)', 'woomp' );
					break;
			}

			$actions['ry_print_ecpay_home_tcat'] = __( 'Print ECPay shipping booking note (tcat)', 'woomp' );
			$actions['ry_print_ecpay_home_ecan'] = __( 'Print ECPay shipping booking note (ecan)', 'woomp' );

			return $actions;
		}

		public function print_shipping_note( $redirect_to, $action, $ids ) {
			if ( false !== strpos( $action, 'ry_print_ecpay_' ) ) {
				$redirect_to = add_query_arg(
					array(
						'orderid'  => implode( ',', $ids ),
						'type'     => substr( $action, 15 ),
						'noheader' => 1,
					),
					admin_url( 'admin.php?page=ry_print_ecpay_shipping' )
				);
				wp_redirect( $redirect_to );
				exit();
			}

			return esc_url_raw( $redirect_to );
		}



	}
	WooMP_Order::init();
}
