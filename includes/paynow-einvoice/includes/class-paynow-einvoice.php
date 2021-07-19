<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.paynow.com.tw/
 * @since      1.0.0
 *
 * @package    Paynow_Einvoice
 * @subpackage Paynow_Einvoice/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Paynow_Einvoice
 * @subpackage Paynow_Einvoice/includes
 * @author     PayNow <hello@paynow.com.tw>
 */
class Paynow_Einvoice {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Paynow_Einvoice_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	public $sandbox;

	public $log_enabled = false;

	/** @var WC_Logger Logger instance */
	public $log = false;

	protected $log_context;

	private $mer_id;
	private $mer_password;
	private $api_url;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'PAYNOW_EINVOICE_VERSION' ) ) {
			$this->version = PAYNOW_EINVOICE_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'paynow-einvoice';

		$this->sandbox = ( get_option( 'wc_settings_tab_paynow_einvoice_sandbox') == 'yes' )? true : false;
		$this->log_enabled  =  ( 'yes' === get_option( 'paynow_einvoice_log_enabled', 'no' ) )? true : false;

		$this->mem_cid      = get_option('wc_settings_tab_mem_cid');
		$this->mem_password = get_option('wc_settings_tab_mem_password');
		$this->api_url      = ($this->sandbox)? 'https://testinvoice.paynow.com.tw': 'https://invoice.paynow.com.tw';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		$this->loader->add_filter( 'woocommerce_get_settings_pages', $this, 'paynow_einvoice_add_settings', 15);


		if (  get_option( 'wc_settings_tab_active_paynow_einvoice' ) === 'yes' ) {

			if (empty($this->mem_cid) || empty($this->mem_password)) {
				$this->loader->add_action( 'admin_notices',   $this, 'paynow_unset_credential_wanrning' );
			}

			//顯示電子發票欄位
			$this->loader->add_action( 'woocommerce_after_order_notes', $this, 'paynow_einvoice_field' );

			//成立訂單後 inside create_order and just after $order->save()
			$this->loader->add_action( 'woocommerce_checkout_update_order_meta', $this, 'paynow_update_order_einvoice_data' );

			//當訂單狀態改變時
			$this->loader->add_action( 'woocommerce_order_status_changed', $this, 'paynow_after_order_status_changed', 10, 3 );

			//order action
			$this->loader->add_filter( 'bulk_actions-edit-shop_order',  $this, 'paynow_register_paynow_actions' );
			$this->loader->add_filter( 'handle_bulk_actions-edit-shop_order', $this, 'paynow_bulk_action_handler', 10, 3 );
			$this->loader->add_action( 'admin_notices',   $this, 'paynow_bulk_action_admin_notice' );

			//手動開立發票
			$this->loader->add_action( 'wp_ajax_paynow_issue_einvoice', $this, 'paynow_ajax_issue_einvoice' );

			//作廢發票
			$this->loader->add_action( 'wp_ajax_paynow_cancel_einvoice', $this, 'paynow_ajax_cancel_einvoice' );

			//發票資訊
			$this->loader->add_action( 'add_meta_boxes', $this, 'paynow_admin_order_meta_boxes' );
			$this->loader->add_action( 'woocommerce_order_details_after_order_table', $this, 'paynow_ei_detail_after_order_table', 10, 1 );

			//驗證結帳欄位
			$this->loader->add_action( 'woocommerce_checkout_process', $this, 'paynow_validate_einvoice_fields', 10, 1);

			$this->loader->add_filter('manage_edit-shop_order_columns', $this, 'paynow_columns_head');
            $this->loader->add_action('manage_shop_order_posts_custom_column', $this, 'paynow_columns_content', 10, 2);

			$this->loader->add_action('paynow_einvoice_after_issued_success', $this, 'paynow_get_einvoice_url', 10, 2);

		}
	}

	public function paynow_after_order_status_changed( $order_id, $old_status, $new_status ) {

		$order = wc_get_order( $order_id);
		if ( !$order )
			return;

		//手動開立，不進行後續動作
		if ( get_option( 'wc_settings_tab_issue_mode' ) == 'manual')
			return;

		$issue_ei_order_status = get_option( 'wc_settings_tab_issue_at' );

		//訂單狀態非可發行狀態
		if ( $new_status != $issue_ei_order_status )
			return;

		//已發行
		$is_issued = get_post_meta( $order_id, '_paynow_ei_issued', true );
		if ( !empty($is_issued) || $is_issued == 'yes' ) {
			return;
		}

		$result  = $this->issue_einvoice( array( $order_id ) );
		$this->pn_write_log($result);

		if( count($result) > 1 ){

			update_post_meta( $order_id, '_paynow_ei_issued', 'yes');

			if (array_key_exists( $order_id, $result['invoices'] ) ) {

				update_post_meta( $order_id, '_paynow_ei_result_invoice_number', $result['invoices'][$order_id]);
				$order->add_order_note( __( 'E-Invoice issued successfully.').$result['invoices'][$order_id]);

				do_action( 'paynow_einvoice_after_issued_success', $order_id, $result['invoices'][$order_id] );
			}

		} else {

			$order->add_order_note( __('E-Invoice issued failed.').$result[0] );

		}
	}

	//手動開立發票可以在任何訂單狀態下執行
	function paynow_ajax_issue_einvoice() {

		if ( check_ajax_referer( 'paynow_issue_einvoice', '_wpnonce', false ) ) {

			$order_id = $_GET['order_id'];
			$order = wc_get_order( $order_id );

			//已發行
			$is_issued = get_post_meta( $order_id, '_paynow_ei_issued', true );
			if ( !empty($is_issued) || $is_issued == 'yes' ) {
				wp_send_json_error( array( 'message' => __('E-Invoice is already issued.', 'paynow-einvoice') ));
			}

			$result  = $this->issue_einvoice( array( $order_id ) );
			$this->pn_write_log('===>ajax issue einvoice');
			$this->pn_write_log($result);

			if( count($result) > 1 ) {

				$this->log( 'issue invoice success' );
				update_post_meta( $order_id, '_paynow_ei_issued', 'yes');
				//save einvoice data to order post meta

				if ( array_key_exists( $order_id, $result['invoices'] ) ) {
					update_post_meta( $order_id, '_paynow_ei_result_invoice_number', $result['invoices'][$order_id]);
					$order->add_order_note( __( 'E-Invoice issued successfully.', 'paynow-einvoice').$result['invoices'][$order_id]);
					do_action( 'paynow_einvoice_after_issued_success', $order_id, $result['invoices'][$order_id] );
					wp_send_json_success( array( 'order_id'=>$order_id));
				}

			} else {

				$this->log('issue invoice failed' );
				$this->log( $result[0] );
				$order->add_order_note( __('E-Invoice issued failed').', '.$result[0] );

				wp_send_json_error( array( 'message' => __( 'E-Invoice issued failed.', 'paynow-einvoice' ). $result[0] ));

			}

		} else {
			wp_send_json_error('unsecure ajax call');
		}



		wp_die();
	}

	function paynow_register_paynow_actions() {
		$bulk_actions['paynow_bulk_issue_einvoice'] = __('Issue PayNow E-Invoice', 'paynow-einvoice');
		return $bulk_actions;
	}

	function paynow_bulk_action_handler( $redirect_to, $doaction, $post_ids ) {
		$allowed_actions = array( 'paynow_bulk_issue_einvoice' );
	  	if( !in_array( $doaction, $allowed_actions ) ) return $redirect_to;

		$already_issued_orders = array();
		$issued_orders = array();

		foreach ( $post_ids as $post_id ) {
			$order = wc_get_order( $post_id);

			//已發行
			$is_issued = get_post_meta( $post_id, '_paynow_ei_issued', true );
			if ( !empty($is_issued) || $is_issued == 'yes' ) {
				$already_issued_orders[] = $order->get_id();
			}

			$issued_orders[] = $order->get_id();

		}

		if (count( $issued_orders ) > 0) {

			$result = $this->issue_einvoice( $issued_orders );
			$this->pn_write_log('====>bulk issue invoice');
			$this->pn_write_log($result);

			if( count($result) > 1 ){

				$issued_orders = $result['invoices'];

				foreach( $issued_orders as $order_id => $invoice_no ) {
					update_post_meta( $order_id, '_paynow_ei_issued', 'yes');
					update_post_meta( $order_id, '_paynow_ei_result_invoice_number', $invoice_no);
					$order->add_order_note( __( 'E-Invoice issued successfully:').$invoice_no);
					do_action( 'paynow_einvoice_after_issued_success', $order_id, $invoice_no );
				}

				$redirect_to = add_query_arg( 'issued_orders', count( $issued_orders ), $redirect_to );
				return $redirect_to;


			} else {

				$order->add_order_note( __('E-Invoice issued failed:').$result[0] );
				$redirect_to = add_query_arg( 'issue_invoice_error', $result[0], $redirect_to );
				return $redirect_to;

			}

		}

		$redirect_to = add_query_arg( 'issued_orders', 0, $redirect_to );
		return $redirect_to;


	}

	function paynow_bulk_action_admin_notice() {
		if ( ! empty( $_REQUEST['issued_orders'] ) ) {
			$issued_count = intval( $_REQUEST['issued_orders'] );
			printf( '<div id="message" class="updated fade">' .
			_n( '%s E-Invoice is issued.',
				'%s E-Invoice are issued',
				$issued_count,
				'paynow_issue_ordr'
			) . '</div>', $issued_count );
		}

		if ( ! empty( $_REQUEST['issue_invoice_error'] ) ) {
			$error = $_REQUEST['issue_invoice_error'];
			printf( '<div id="error" class="updated fade">' .
			"E-Invoice issued failed：%s".'</div>', $error );
		}
	}

	function paynow_ajax_cancel_einvoice() {

		if ( check_ajax_referer( 'paynow_cancel_einvoice', '_wpnonce', false ) ) {

			$order_id = $_GET['order_id'];
			$order = wc_get_order( $order_id );

			//已發行
			// $is_issued = get_post_meta( $order_id, '_paynow_ei_issued', true );
			// if ( !empty($is_issued) || $is_issued !== 'yes' ) {
			// 	// $this->logger->debug('電子發票尚未開立', $this->log_context );
			// 	wp_send_json_error(array('message'=>'電子發票尚未開立' ));
			// }

			$invoice_no = get_post_meta( $order_id, '_paynow_ei_result_invoice_number', true );

			$result = $this->cancel_invoice( $invoice_no );

			if( count($result) > 1 ){
				$order->add_order_note( __('Cancel E-Invoice Successfully:', 'paynow-einvoice').$invoice_no);
				wp_send_json_success(array('order_id'=>$order_id));
			} else {
				$order->add_order_note( __('Cancel E-Invoice Failed:', 'paynow-einvoice').$result[0]);
				wp_send_json_error( array( 'message'=> __('Cancel E-Invoice Failed:', 'paynow-einvoice') . $result[0] ));
			}

		} else {
			wp_send_json_error();
		}

		wp_die();
	}

	// 開立發票
	private function issue_einvoice( $order_ids ) {

		$ei_datas = array();

		foreach ( $order_ids as $order_id ) {

			$order = wc_get_order( $order_id );

			if ( !$order ) {
				continue;
			}

			$issue_type  = get_post_meta( $order->get_id(), '_paynow_ei_issue_type', true );
			$this->pn_write_log('發行方式：'.$issue_type);

			//統一編號，若為個人為空
			$carrier_type = '';//載具類型
			$$carrier_id1 = '';//載具明碼
			$$carrier_id2 = '';//載具隱碼

			$buyer_addr = $this->get_buyer_addr( $order );

			if ( $issue_type == 'b2b') {

				$ubn = get_post_meta( $order->get_id(), '_paynow_ei_ubn', true );
				$buyer_name = get_post_meta( $order->get_id(), '_paynow_ei_buyer_name', true);

			} elseif ( $issue_type == 'b2c' ) {

				$ubn = '';
				$buyer_name = $order->get_billing_last_name().$order->get_billing_first_name();

				$selected_carrier_type = get_post_meta( $order->get_id(), '_paynow_ei_carrier_type', true);
				if ($selected_carrier_type == 'ei_carrier_type_easycard_code') {
					$carrier_type = '1K0001';//悠遊卡
				} elseif ($selected_carrier_type == 'ei_carrier_type_cdc_code') {
					$carrier_type = 'CQ0001';//自然人憑證
				} elseif ($selected_carrier_type == 'ei_carrier_type_mobile_code') {
					$carrier_type = '3J0002';//通用載具
					$carrier_id1 = get_post_meta( $order->get_id(), '_paynow_ei_carrier_num', true );
					$carrier_id2 = $carrier_id1;
				}
			} else {
				//donate
				$ubn        = '';
				$buyer_name = $order->get_billing_last_name().$order->get_billing_first_name();
				$buyer_addr = '';
				$love_code  = ( $issue_type == 'donate' )? get_post_meta( $order->get_id(), '_paynow_ei_donate_org', true) : '';//愛心碼
			}

			//1=應稅,2=零稅率,3=免稅,
			//9=混合應稅與免稅或零稅率(限收銀機發票無法分辨時使用) =>不支援
			$tax_type = get_option( 'wc_settings_tab_tax_type' );
			if ( $tax_type == '1' ) {
				$tax_rate = 5;
			} elseif ( $tax_type == '2' || $tax_type == '3' ) {
				$tax_rate = 0;
			}



			$comment      = '';
			$comment      = apply_filters( 'paynow_ei_comment', $comment );//發票備註，字數限 25 字。


			$order_items = $order->get_items( array('line_item', 'fee', 'shipping') );
			if ( !is_wp_error( $order_items ) ) {
				foreach( $order_items as $item_id => $order_item ) {

					$ei_datas[] = array(

						'orderno'        	=>  "'".$order->get_order_number(),  //商店訂單編號

						'buyer_id'			=>  "'".$ubn,//統編
						'buyer_name'		=>  "'".$buyer_name, //買受人名稱
						'buyer_add'		    =>  "'".$buyer_addr, //若填入代表要寄送紙本發票，不寄送紙本發票請填空，如最前面為 BRING+ 地址則會保留地址資訊但不寄送發票
						'buyer_phone'		=>  "'".$order->get_billing_phone(),
						'buyer_email'		=>  "'".$order->get_billing_email(),

						'CarrierType'		=>  "'".$carrier_type,
						'CarrierID_1'		=>  "'".$carrier_id1,
						'CarrierID_2'		=>  "'".$carrier_id1,//發票隱碼
						'LoveCode'			=>  "'".$love_code,

						'Description'		=>  "'".$order_item->get_name(),
						'Quantity'			=>  "'".$qty = ($order_item->get_type() == 'line_item')? $order_item->get_quantity(): '1',
						'UnitPrice'			=>  "'".($order_item->get_total() / $qty),
						'Amount'			=>  "'".$order_item->get_total(),
						'Remark'			=>  "'".$comment,

						'ItemTaxtype'		=>  "'".$tax_type,
						'IsPassCustoms'     =>  "'",//1:未經海關出口,2:經海關出口 (零稅率為必填非零稅率發票請留空)

					);
				}
			}


		}//endforeach


		$this->pn_write_log( '===>ei_datas' );
		$this->pn_write_log( $ei_datas );

		$result = $this->do_issue( $ei_datas );
		$this->pn_write_log($result);

		return $result;


	}

	private function do_issue( $ei_datas ) {
		$arrContextOptions = array(
			'ssl' => array(
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true,
				'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT
			)
		);
		$options = array(
			'soap_version' => SOAP_1_2,
			'exceptions' => true,
			'trace' => 1,
			'cache_wsdl' => WSDL_CACHE_NONE,
			'stream_context' => stream_context_create($arrContextOptions)
		);

		$client = new SoapClient( $this->api_url.'/PayNowEInvoice.asmx?wsdl', $options );

		$str = $this->build_invoice_str( $ei_datas );
		$this->pn_write_log('csvStr:'.$str);

		$encoded_s = urlencode(base64_encode($str));

		$param_ary =  array(
			'mem_cid'=> $this->mem_cid,
			'mem_password'=> $this->mem_password,
			'csvStr'=>  $encoded_s
		);

		$this->pn_write_log($param_ary);
		$aryResult = $client->__soapCall('UploadInvoice_Patch',array('parameters' => $param_ary) );
		$this->pn_write_log('====>UploadInvoice_Patch');
		$this->pn_write_log($aryResult);
		$result = $aryResult->UploadInvoice_PatchResult;

		if ( strpos($result, 'S_') === 0 ) {
			$first_comma_pos = strpos($result, ',');
			$invoice_result = substr($result, $first_comma_pos+1, strlen($result));
			$response[0] = 'S_';
			$invoices = explode(',', $invoice_result);
			foreach($invoices as $invoice) {
				$invoice_data = explode('_', $invoice);
				$response['invoices'][$invoice_data[0]] = $invoice_data[1];
			}
		} else {
			$response = array($result);
		}

		return $response;
	}

	private function cancel_invoice( $invoice_no ) {
		$arrContextOptions = array(
			'ssl' => array(
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true,
				'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT
			)
		);
		$options = array(
			'soap_version' => SOAP_1_2,
			'exceptions' => true,
			'trace' => 1,
			'cache_wsdl' => WSDL_CACHE_NONE,
			'stream_context' => stream_context_create($arrContextOptions)
		);

		$client = new SoapClient( $this->api_url.'/PayNowEInvoice.asmx?wsdl', $options );

		$param_ary =  array(
			'mem_cid'=> $this->mem_cid,
			'InvoiceNo'=>  $invoice_no
		);
		$this->pn_write_log($param_ary);

		$aryResult = $client->__soapCall( 'CancelInvoice_I',array('parameters' => $param_ary ) );
		$this->pn_write_log($aryResult);
		$result = $aryResult->CancelInvoice_IResult;
		if ($result === 'S') {
			// $response = explode(',', $result);
			$response = $result;
		} else {
			$response = array($result);
		}
		return $response;

	}

	function paynow_admin_order_meta_boxes() {
		global $post;

		add_meta_box( 'paynow-ei-meta-boxes', __('PayNow E-Invoice', 'paynow-einvoice'), array(
			$this,
			'paynow_ei_admin_meta'
		), 'shop_order', 'side', 'high' );

	}

	function paynow_ei_admin_meta( $post ) {

		$issue_type = get_post_meta( $post->ID, '_paynow_ei_issue_type', true);
		$carrier_type = get_post_meta( $post->ID, '_paynow_ei_carrier_type', true);

		echo '<div>索取方式：'.PayNow_EInvoice_Issue_Type::getType($issue_type).'</div>';

		if ( $issue_type == PayNow_EInvoice_Issue_Type::B2B) {
			echo '<div>'.__('Buyer Name', 'paynow-einvoice').'：'.get_post_meta( $post->ID, '_paynow_ei_buyer_name', true).'</div>';
			echo '<div>'.__( 'Unified Business NO', 'paynow-einvoice' ).'：'.get_post_meta( $post->ID, '_paynow_ei_ubn', true).'</div>';
		}

		if ( $issue_type == PayNow_EInvoice_Issue_Type::B2C ) {
			echo '<div>'.__( 'Carrier Number', 'paynow-einvoice' ).'：'.get_post_meta( $post->ID, '_paynow_ei_carrier_num', true).'</div>';
		}

		if ( $issue_type == PayNow_EInvoice_Issue_Type::DONATE ) {
			echo '<div>'.__( 'Love Code', 'paynow-einvoice' ).'：'.get_post_meta( $post->ID, '_paynow_ei_donate_org', true).'</div>';
		}

		if (get_post_meta( $post->ID, '_paynow_ei_issued', true) == 'yes' ) {
			$invoice_url = get_post_meta( $post->ID, '_paynow_invoice_url', true);

			if ($invoice_url) {
				echo '<div>'.__( 'E-Invoice NO', 'paynow-einvoice' ) .'：<a href="'.$invoice_url.'" target="_blank">'. get_post_meta( $post->ID, '_paynow_ei_result_invoice_number', true ) . '</a></div>';
			} else {
				echo '<div>'.__( 'E-Invoice NO', 'paynow-einvoice' ) .'：'. get_post_meta( $post->ID, '_paynow_ei_result_invoice_number', true ) . '</div>';
			}
			$url = wp_nonce_url(
				admin_url(add_query_arg(
						array(
							'action'   => 'paynow_cancel_einvoice',
							'order_id' => $post->ID),
							'admin-ajax.php')),
						'paynow_cancel_einvoice');
			echo '<div><a href="'.$url.'" class="button cancel_einvoice" data-id="'.$post->ID.'">'.__('Cancel E-Invoice', 'paynow-einvoice').'</a>' . '</div>';

		} else {
			echo '<div>'.__( 'E-Invoice NO', 'paynow-einvoice' ). ':'.__('N/A', 'paynow-einvoice') . '</div>';
			$url = wp_nonce_url(
				admin_url(add_query_arg(
						array(
							'action'   => 'paynow_issue_einvoice',
							'order_id' => $post->ID),
							'admin-ajax.php')),
						'paynow_issue_einvoice');
			echo '<div><a href="'.$url.'" class="button issue_einvoice" data-id="'.$post->ID.'">'.__('Issue E-Invoice', 'paynow-einvoice').'</a></div>';
		}

	}

	function paynow_ei_detail_after_order_table( $order ) {

		echo '<h2>'.__( 'PayNow E-Invoice Details', 'paynow-einvoice' ).'</h2><table class="shop_table paynow-einvoice-details"><tbody>';

		$issue_type = get_post_meta( $order->get_id(), '_paynow_ei_issue_type', true);
		$carrier_type = get_post_meta( $order->get_id(), '_paynow_ei_carrier_type', true);

		echo '<tr><td><strong>'._x( 'Issue Type', 'checkout', 'paynow-einvoice' ).'</strong></td>';
		echo '<td>' . PayNow_EInvoice_Issue_Type::getType($issue_type) . '</td></tr>';

		if ( $issue_type == PayNow_EInvoice_Issue_Type::B2B) {

			echo '<tr><td><strong>'.__( 'Buyer Name', 'paynow-einvoice' ).'</strong></td>';
			echo '<td>' . get_post_meta( $order->get_id(), '_paynow_ei_buyer_name', true) . '</td></tr>';
			echo '<tr><td><strong>'.__( 'Unified Business NO', 'paynow-einvoice' ).'</strong></td>';
			echo '<td>' . get_post_meta( $order->get_id(), '_paynow_ei_ubn', true) . '</td></tr>';
		}

		if ( $issue_type == PayNow_EInvoice_Issue_Type::B2C) {
			echo '<tr><td><strong>'.__( 'Carrier Number', 'paynow-einvoice' ).'</strong></td>';
			echo '<td>' . get_post_meta( $order->get_id(), '_paynow_ei_carrier_num', true) . '</td></tr>';
		}

		if ( $issue_type == PayNow_EInvoice_Issue_Type::DONATE ) {
			echo '<tr><td><strong>'.__( 'Love Code', 'paynow-einvoice' ).'</strong></td>';
			echo '<td>' . get_post_meta( $order->get_id(), '_paynow_ei_donate_org', true) . '</td></tr>';
		}

		if (get_post_meta( $order->get_id(), '_paynow_ei_issued', true) != 'yes' ) {
			echo '<tr><td><strong>'.__( 'Issue Status', 'paynow-einvoice' ).'</strong></td>';
			echo '<td>未開立</td></tr>';
		} else {
			$invoice_url = get_post_meta( $order->get_id(), '_paynow_invoice_url', true);
			echo '<tr><td><strong>'.__( 'E-Invoice NO', 'paynow-einvoice' ).'</strong></td>';
			if ($invoice_url) {
				echo '<td><a href="'.$invoice_url.'" target="_blank">' . get_post_meta( $order->get_id(), '_paynow_ei_result_invoice_number', true ) . '</a></td></tr>';
			} else {
				echo '<td>' . get_post_meta( $order->get_id(), '_paynow_ei_result_invoice_number', true ) . '</td></tr>';
			}


		}

		echo '</tbody></table>';


	}

	function paynow_validate_einvoice_fields( ){

		$this->pn_write_log($_POST);

		$issue_type = $_POST['paynow_ei_issue_type'];
		if ($issue_type == 'b2b') {
			$buyer_name = $_POST['paynow_ei_buyer_name'];
			$buyer_ubn = $_POST['paynow_ei_ubn'];
			if (!$buyer_name || !$buyer_ubn) {
				wc_add_notice( __('Please input the company name and Unified Business NO', 'paynow-einvoice'), 'error' );
			}
		} elseif ($issue_type == 'b2c') {
			$carrier_num = $_POST['paynow_ei_carrier_num'];
			if (!$carrier_num) {
				wc_add_notice( __('Please input the carrier number', 'paynow-einvoice'), 'error' );
			}
		}

	}

    function paynow_columns_head( $defaults ) {
        $defaults['paynow_einvoice'] = __('PayNow E-Invoice', 'paynow-einvoice');
        return $defaults;
    }

    function paynow_columns_content( $column_name, $post_id ) {
        $screen = get_current_screen();
        if ( $column_name == 'paynow_einvoice' ) {
			$is_issued = get_post_meta( $post_id, '_paynow_ei_issued', true );
			$einvoice_no = get_post_meta( $post_id, '_paynow_ei_result_invoice_number', true );
            if ( $is_issued ) {
                echo '<span class="paynow dashicons dashicons-text-page issued" title='.$einvoice_no.'></span>';
            } else {
                echo '<span class="paynow dashicons dashicons-text-page unissue"></span>';
            }
        }
    }

	function paynow_get_einvoice_url( $order_id, $invoice_no ) {

		$arrContextOptions = array(
			'ssl' => array(
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true,
				'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT
			)
		);
		$options = array(
			'soap_version' => SOAP_1_2,
			'exceptions' => true,
			'trace' => 1,
			'cache_wsdl' => WSDL_CACHE_NONE,
			'stream_context' => stream_context_create($arrContextOptions)
		);

		$client = new SoapClient( $this->api_url.'/PayNowEInvoice.asmx?wsdl', $options );

		$param_ary =  array(
			'mem_cid'=> $this->mem_cid,
			'InvoiceNo'=>  $invoice_no
		);


		$aryResult = $client->__soapCall( 'Get_InvoiceURL_I',array( 'parameters' => $param_ary ) );
		$this->pn_write_log('===>Get_InvoiceURL_I');
		$this->pn_write_log($aryResult);
		$invoice_url = (empty($aryResult->Get_InvoiceURL_IResult))? '': $aryResult->Get_InvoiceURL_IResult;

		update_post_meta( $order_id, '_paynow_invoice_url', $invoice_url );
		return $invoice_url;

	}

	private function build_invoice_str( $datas ) {
		$csvStr = '';
		$count = count($datas);
		$index = 1;
		foreach($datas as $data) {
			$csvStr .= implode(',', $data);
			if ($index < $count) {
				$csvStr .= PHP_EOL;
				$index++;
			}
		}
		return $csvStr;
	}

	private function get_buyer_addr( $order ) {
		if ( $order ) {
			//不寄送發票
			return 'BRING'. $order->get_billing_state().$order->get_billing_city().$order->get_billing_address_1().$order->get_billing_address_2();
		} else {
			return '';
		}
	}

	public function paynow_einvoice_add_settings() {
		require_once PAYNOW_EINVOICE_PLUGIN_DIR . 'includes/settings/class-paynow-einvoice-settings-tab.php';
		return new WC_Settings_Tab_PayNow_EInvoice();
	}

	public function paynow_unset_credential_wanrning() {
	?>
		<div class="error">
        <p><?php _e( 'Notice: You need to set up Merchant ID and Merchant Password ', 'paynow-einvoice' ); ?>  <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=paynow&section=einvoice' ); ?>"><?php _e( 'PayNow E-Invoice Setting', 'paynow-einvoice') ?></a></p>
    	</div>
	<?php
	}


	public function paynow_einvoice_field( $checkout ) {
		echo '<div id="paynow-ei-fields"><h3>發票資訊</h3>';

		echo '<div id="paynow-ei-issue-type" class="paynow-field">';
		woocommerce_form_field( 'paynow_ei_issue_type',
			array(
	        	'type'         => 'select',
	        	'class'        => array( 'input-checkbox' ),
	        	'label'        => _x('Issue Type', 'checkout', 'paynow-einvoice'),
				'options'      => array(
					'b2c'    => __('Personal E-Invoice', 'paynow-einvoice'),
					'b2b'    => __('Company E-Invoice', 'paynow-einvoice'),
					'donate' => __('Donate', 'paynow-einvoice'),
				)
			),
			$checkout->get_value( 'paynow_ei_issue_type' )
        );
        echo '</div>';

		echo '<div id="paynow-ei-carrier-type" class="paynow-field">';
		woocommerce_form_field( 'paynow_ei_carrier_type',
			array(
	        	'type'         => 'select',
	        	'class'        => array( 'input-checkbox' ),
	        	'label'        => __('Carrier Type', 'paynow-einvoice'),
	        	'options'      => $this->paynow_get_carrier_type()

			),
			$checkout->get_value( 'paynow_ei_carrier_type' )
        );
        echo '</div>';

		echo '<div id="paynow-ei-company-title" class="paynow-field">';
        woocommerce_form_field( 'paynow_ei_buyer_name',
			array(
				'type'			=> 'text',
				'placeholder'	=> __('Buyer Name', 'paynow-einvoice'),
				'required'		=> false,
			),
			$checkout->get_value( 'paynow_ei_buyer_name' )
		);
		echo '</div>';

		echo '<div id="paynow-ei-ubn" class="paynow-field">';
		woocommerce_form_field( 'paynow_ei_ubn',
			array(
				'type'			=> 'text',
				'placeholder'	=> __('Unified Business NO', 'paynow-einvoice'),
				'required'		=> false,
				'default'		=> ''
			),
			$checkout->get_value( 'paynow_ei_ubn' )
		);
		echo '</div>';

		echo '<div id="paynow-ei-carrier-num" class="paynow-field">';
		woocommerce_form_field( 'paynow_ei_carrier_num',
			array(
				'type'			=> 'text',
				'label'			=> __( 'Carrier Number', 'paynow-einvoice' ),
				'placeholder'	=> __( 'Please input the Carrier Number', 'paynow-einvoice' ),
				'required'		=> false,
				'default'		=>''
			),
			$checkout->get_value('paynow_ei_carrier_num')
		);
		echo '</div>';

		echo '<div id="paynow-ei-org" class="paynow-field">';
		woocommerce_form_field( 'paynow_ei_donate_org',
			array(
				'type'			=> 'select',
				'label'			=> __('Donate Organization', 'paynow-einvoice'),
				'required'		=> false,
				'options'		=> $this->paynow_get_donate_org()
			),
			$checkout->get_value('paynow_ei_donate_org')
		);
		echo '</div>';

        echo '</div>';
	}

	//儲存使用者結帳時的 einvoice 資料
	public function paynow_update_order_einvoice_data( $order_id ) {

		$order = wc_get_order( $order_id );

		update_post_meta( $order->get_id(), '_paynow_ei_issue_type', $_POST['paynow_ei_issue_type'] );
		update_post_meta( $order->get_id(), '_paynow_ei_carrier_type', $_POST['paynow_ei_carrier_type'] );

		update_post_meta( $order->get_id(), '_paynow_ei_buyer_name', $_POST['paynow_ei_buyer_name'] );
		update_post_meta( $order->get_id(), '_paynow_ei_ubn', $_POST['paynow_ei_ubn'] );

		update_post_meta( $order->get_id(), '_paynow_ei_carrier_num', $_POST['paynow_ei_carrier_num'] );
		update_post_meta( $order->get_id(), '_paynow_ei_donate_org', $_POST['paynow_ei_donate_org'] );

	}

	//結帳顯示的選項
	public function paynow_get_carrier_type() {
		$carriers = array();

		if (get_option( 'wc_settings_tab_carrier_type_mobile_code') == 'yes') {
			$carriers['ei_carrier_type_mobile_code'] = __('Mobile Code', 'paynow-einvoice');
		}

		if (get_option( 'wc_settings_tab_carrier_type_cdc_code' ) == 'yes') {
			$carriers['ei_carrier_type_cdc_code'] = __( 'Citizen Digital Certificate', 'paynow-einvoice' );
		}

		if (get_option( 'wc_settings_tab_carrier_type_easycard_code' ) == 'yes') {
			$carriers['ei_carrier_type_easycard_code'] = __('Easy Card', 'paynow-einvoice');
		}

		return $carriers;

	}

	public function paynow_get_donate_org() {
		$orgs = array();
		$org_strings = array_map( 'trim', explode("\n", get_option( 'wc_settings_tab_donate_org', true )) );
		foreach ($org_strings as $value) {
			list($k, $v) = explode('|', $value);
			$orgs[ $k ] = $v;
		}
		return $orgs;
	}

	public function pn_write_log($log)
	{

		if (is_array($log) || is_object($log)) {
			error_log(print_r($log, true));
		} else {
			error_log($log);
		}
	}

	public function log($message, $level = 'info') {
		if ($this->log_enabled) {
			if (empty($this->log)) {
				$this->log = new WC_Logger();
			}
			$this->log( $level, $message, array( 'source' => 'paynow-einvoice' ) );
		}
	}

	// 	private function get_amt( $order, $tax_rate ) {
	// 	//四捨五入
	// 	if ( $tax_rate == 5 ) {
	// 		$amt = round( $order->get_total() / ( 1 + $tax_rate * 0.01 ) );
	// 	} elseif ( $tax_rate == 0 ) {
	// 		$amt = $order->get_total();
	// 	}

	// 	return $amt;
	// }

	// private function get_carrier_type( $user_carrier_type, $category ) {
	// 	if ( $category == 'B2B')
	// 		return '';

	// 	if ( $user_carrier_type == 'ei_carrier_type_mobile_code' ) {
	// 		return '0';
	// 	}

	// 	if ( $user_carrier_type == 'ei_carrier_type_cdc_code' ) {
	// 		return '1';
	// 	}

	// 	if ( $user_carrier_type == 'ei_carrier_type_paynow_member') {
	// 		return '2';
	// 	}

	// }

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Paynow_Einvoice_Loader. Orchestrates the hooks of the plugin.
	 * - Paynow_Einvoice_i18n. Defines internationalization functionality.
	 * - Paynow_Einvoice_Admin. Defines all hooks for the admin area.
	 * - Paynow_Einvoice_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-paynow-einvoice-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-paynow-einvoice-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-paynow-einvoice-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-paynow-einvoice-public.php';

		require_once PAYNOW_EINVOICE_PLUGIN_DIR . 'includes/class-paynow-einvoice-issue-type.php';

		$this->loader = new Paynow_Einvoice_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Paynow_Einvoice_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Paynow_Einvoice_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Paynow_Einvoice_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Paynow_Einvoice_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Paynow_Einvoice_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
