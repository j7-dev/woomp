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
			add_filter( 'wc_order_statuses', array( $class, 'add_order_statuses' ) );
			add_filter( 'woocommerce_reports_order_statuses', array( $class, 'add_order_statuses' ) );
			add_filter( 'woocommerce_order_is_paid_statuses', array( $class, 'add_report_paid_statuses' ) );
			add_filter( 'wp_ajax_delete_shipping_ecpay_cvs', array( $class, 'delete_shipping_ecpay_cvs' ) );

			add_filter( 'manage_shop_order_posts_columns', array( $class, 'shop_order_columns' ), 11, 1 );
			add_action( 'manage_shop_order_posts_custom_column', array( $class, 'shop_order_column' ), 11, 2 );

			add_filter( 'bulk_actions-edit-shop_order', array( $class, 'bulk_action' ), 99, 1 );
			add_filter( 'handle_bulk_actions-edit-shop_order', array( $class, 'print_shipping_note' ), 10, 3 );

			add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $class, 'add_choose_cvs_btn' ) );
			add_action( 'admin_enqueue_scripts', array( $class, 'enqueue_choose_cvs_script' ) );

			add_action( 'woocommerce_order_status_on-hold', array( $class, 'cancel_atm_exipred_orders' ) );
			add_action( 'woocommerce_cancel_atm_expired_orders', array( $class, 'cancel_atm_exipred_orders' ) );

		}

		/**
		 * 根據付款截止日排程取消訂單
		 */
		public function cancel_atm_exipred_orders() {

			wp_clear_scheduled_hook( 'woocommerce_cancel_atm_expired_orders' );
			$cancel_unpaid_interval = get_option( 'woocommerce_ry_ecpay_atm_expire_date' ) * 24 * 60;
			wp_schedule_single_event( time() + ( absint( $cancel_unpaid_interval ) * 60 ), 'woocommerce_cancel_atm_expired_orders' );

			// $log = new WC_Logger();
			// $log->log( 'info', 'ry-'.wc_print_r( get_option( 'woocommerce_ry_ecpay_atm_expire_date', true ), true ), array( 'source' => 'ods-log' ) );

			// global $wpdb;

			// $unpaid_orders = $wpdb->get_col(
			// $wpdb->prepare(
			//		// @codingStandardsIgnoreStart
			//		"SELECT posts.ID
			//		FROM {$wpdb->posts} AS posts
			//		WHERE   posts.post_type   IN ('" . implode( "','", wc_get_order_types() ) . "')
			//		AND     posts.post_status = 'wc-on-hold'
			//		AND     posts.post_modified < %s",
			//		// @codingStandardsIgnoreEnd
			// gmdate( 'Y-m-d H:i:s', absint( strtotime( '-' . absint( $cancel_unpaid_interval ) . ' MINUTES', current_time( 'timestamp' ) ) ) )
			// )
			// );

			// if ( $unpaid_orders ) {
			// foreach ( $unpaid_orders as $unpaid_order ) {
			// $order = wc_get_order( $unpaid_order );
			// $order->update_status( 'cancelled', __( 'Unpaid order cancelled - time limit reached.', 'woocommerce' ) );
			// }
			// }
		}


		/**
		 * 增加訂單狀態
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
			$new_order_statuses = array();
			foreach ( $order_statuses as $key => $status ) {
				$new_order_statuses[ $key ] = $status;
				if ( 'wc-processing' === $key ) {
					$new_order_statuses['wc-wmp-in-transit'] = '配送中';
				}
			}
			return $new_order_statuses;
		}

		public function add_report_paid_statuses( $statues ) {
			$statues[] = 'wmp-in-transit';
			return $statues;
		}

		public function add_order_is_paid_statuses( $statues ) {
			$statues[] = 'wmp-in-transit';
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
		 * 後台訂單列表修改
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
				} elseif ( get_post_meta( $post_id, '_paynow_shipping_paymentno', true ) ) {
					echo get_post_meta( $post_id, '_paynow_shipping_paymentno', true );
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

			if ( get_option( 'wc_woomp_setting_show_phone', 1 ) === 'yes' ) {
				/**
				 * 加入帳單電話
				 */
				if ( $column == 'billing_address' ) {
					$order = wc_get_order( $post_id );
					echo '<span class="billing-phone" style="display: block;">電話 ' . $order->get_billing_phone() . '</span>';
				}

				/**
				 * 加入運送電話
				 */
				if ( $column == 'shipping_address' ) {
					$order = wc_get_order( $post_id );
					if ( $order->get_shipping_phone() ) {
						echo '<span class="shipping-phone" style="display: block;">電話 ' . $order->get_shipping_phone() . '</span>';
					}
				}
			}
		}

		/**
		 * 訂單列表增加列運托運單批次操作選單
		 */
		public function bulk_action( $actions ) {
			if ( wc_string_to_bool( get_option( 'RY_WT_enabled_ecpay_shipping' ) ) ) {
				switch ( RY_WT::get_option( 'ecpay_shipping_cvs_type' ) ) {
					case 'B2C':
						$actions['ry_print_ecpay_cvs_711']    = __( 'Print ECPay shipping booking note (711)', 'woomp' );
						$actions['ry_print_ecpay_cvs_family'] = __( 'Print ECPay shipping booking note (family)', 'woomp' );
						$actions['ry_print_ecpay_cvs_hilife'] = __( 'Print ECPay shipping booking note (hilife)', 'woomp' );
						break;
					case 'C2C':
						$actions['ry_print_ecpay_cvs_711']    = __( 'Print ECPay shipping booking note (711)', 'woomp' );
						$actions['ry_print_ecpay_cvs_family'] = __( 'Print ECPay shipping booking note (family)', 'woomp' );
						$actions['ry_print_ecpay_cvs_hilife'] = __( 'Print ECPay shipping booking note (hilife)', 'woomp' );
						$actions['ry_print_ecpay_cvs_okmart'] = __( 'Print ECPay shipping booking note (okmart)', 'woomp' );
						break;
				}

				$actions['ry_print_ecpay_home_tcat'] = __( 'Print ECPay shipping booking note (tcat)', 'woomp' );
				$actions['ry_print_ecpay_home_ecan'] = __( 'Print ECPay shipping booking note (ecan)', 'woomp' );
			}
			$actions['wmp_print_hct'] = __( 'Print HCT shipping booking note', 'woomp' );

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
			} elseif ( 'wmp_print_hct' === $action ) {
				set_time_limit( 0 );
				$csv_arr   = array();
				$csv_arr[] = array( '序號', '訂單號', '收件人姓名', '收件人地址', '收件人電話', '託運備註', '商品別編號', '商品數量', '才積重量', '代收貨款', '指定配送日期', '指定配送時間' );

				$filename = current_time( 'Y-m-d' ) . '-hct-export.csv';

				header( 'Pragma: no-cache' );
				header( 'Expires: 0' );
				header( 'Content-Disposition: attachment;filename="' . $filename . '";' );
				header( 'Content-Type: application/csv; charset=UTF-8' );

				$i = 1;

				foreach ( $ids as $id ) {
					$order     = wc_get_order( $id );
					$csv_arr[] = array(
						'serial'           => $i,
						'order_id'         => $id,
						'shipping_name'    => ( $order->get_shipping_last_name() ) ? $order->get_shipping_last_name() . $order->get_shipping_first_name() : $order->get_billing_last_name() . $order->get_billing_first_name(),
						'shipping_address' => ( $order->get_shipping_address_1() ) ? $order->get_shipping_postcode() . $order->get_shipping_state() . $order->get_shipping_city() . $order->get_shipping_address_1() : $order->get_billing_postcode() . $order->get_billing_state() . $order->get_billing_city() . $order->get_billing_address_1(),
						'phone'            => $order->get_billing_phone(),
						'note'             => '',
						'product_num'      => '',
						'qty'              => count( $order->get_items() ),
						'weight'           => '',
						'amount'           => ( 'woomp_cod_gateway' === $order->get_payment_method() ) ? $order->get_total() : '',
						'deliver_date'     => '',
						'deliver_time'     => '',
					);
					$i++;
				}

				for ( $j = 0; $j < count( $csv_arr ); $j++ ) {
					echo $this->csvstr( $csv_arr[ $j ] ) . "\n";
				}
			}
		}

		/**
		 * 確保輸出內容符合 CSV 格式，定義下列方法來處理
		 */
		public function csvstr( array $fields ) {
			$f = fopen( 'php://memory', 'r+' );
			if ( fputcsv( $f, $fields ) === false ) {
				return false;
			}
			rewind( $f );
			$csv_line = stream_get_contents( $f );
			return rtrim( $csv_line );
		}

		/**
		 * 增加訂單頁面重新選擇超商按鈕
		 */
		public function add_choose_cvs_btn( $order ) {
			if ( get_option( RY_WT::$option_prefix . 'enabled_ecpay_shipping', 1 ) === 'yes' ) {
				foreach ( $order->get_items( 'shipping' ) as $item_id => $item ) {
					$method_class = RY_ECPay_Shipping::get_order_support_shipping( $item );
					if ( $method_class !== false && strpos( $method_class, 'cvs' ) !== false ) {
						echo '<div class="edit_address">
						<button type="button" class="button choose-cvs" style="margin-top: 10px;">' . __( 'Update convenience store', 'woomp' ) . ' </button><p style="margin-top: 10px;">'
						. __( 'After choosing cvs, you need update the order to save changing.', 'woomp' ) . '
						</p></div>
						';
					}
				}
			}
		}

		/**
		 * 註冊重新選擇超商 JS
		 */
		public function enqueue_choose_cvs_script() {
			global $pagenow;
			if ( 'post.php' === $pagenow && isset( $_GET['post'] ) && 'shop_order' === get_post_type( $_GET['post'] ) && get_option( RY_WT::$option_prefix . 'enabled_ecpay_shipping', 1 ) === 'yes' ) {
				$order = wc_get_order( $_GET['post'] );
				foreach ( $order->get_items( 'shipping' ) as $item_id => $item ) {
					$method_class = RY_ECPay_Shipping::get_order_support_shipping( $item );
					if ( $method_class !== false && strpos( $method_class, 'cvs' ) !== false ) {
						list($MerchantID, $HashKey, $HashIV, $CVS_type) = RY_ECPay_Shipping::get_ecpay_api_info();

						$choosed_cvs = '';

						if ( isset( $_POST['MerchantID'] ) && $_POST['MerchantID'] == $MerchantID ) {
							$choosed_cvs = array(
								'CVSStoreID'   => wc_clean( wp_unslash( $_POST['CVSStoreID'] ) ),
								'CVSStoreName' => wc_clean( wp_unslash( $_POST['CVSStoreName'] ) ),
								'CVSAddress'   => wc_clean( wp_unslash( $_POST['CVSAddress'] ) ),
								'CVSTelephone' => wc_clean( wp_unslash( $_POST['CVSTelephone'] ) ),
							);
						}

						wp_register_script( 'wmp-admin-shipping', WOOMP_PLUGIN_URL . 'admin/js/choose-cvs.js', array( 'jquery' ), null, false );

						wp_localize_script(
							'wmp-admin-shipping',
							'ECPayInfo',
							array(
								'postUrl'  => RY_ECPay_Shipping_Api::get_map_post_url(),
								'postData' => array(
									'MerchantID'       => $MerchantID,
									'LogisticsType'    => $method_class::$LogisticsType,
									'LogisticsSubType' => $method_class::$LogisticsSubType . ( ( 'C2C' == $CVS_type ) ? 'C2C' : '' ),
									'IsCollection'     => 'Y',
									'ServerReplyURL'   => esc_url( WC()->api_request_url( 'ry_ecpay_map_callback' ) ),
									'ExtraData'        => 'ry' . $order->get_id(),
								),
								'newStore' => $choosed_cvs,
							)
						);

						wp_enqueue_script( 'wmp-admin-shipping' );

					}
				}
			}
		}

	}
	WooMP_Order::init();
}
