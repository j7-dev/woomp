<?php
namespace WOOMPECPAYINVOICE\Templates;

defined( 'ABSPATH' ) || exit;

class Checkout {

	public static function init() {
		$class = new self();
		add_action( 'wp_enqueue_scripts', array( $class, 'enqueue_scripts' ) );
		add_action( 'woocommerce_after_checkout_billing_form', array( $class, 'set_invoice_field' ) );
		add_action( 'woocommerce_checkout_process', array( $class, 'set_invoice_field_validate' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $class, 'set_invoice_meta' ) );
	}

	/**
	 * 發票欄位
	 */
	public function set_invoice_field() {

		if ( '0' === WC()->cart->total && strpos( $this->get_cart_info( 'product_type' ), 'subscription' ) === false ) {
			return;
		}

		// 發票開立類型。個人、公司、捐贈發票
		$this->add_wc_field(
			'invoice-type',
			'select',
			__( 'Invoice Type', 'woomp' ),
			array(),
			'invoice-label',
			array( // 發票開立選項
				'individual' => __( 'individual', 'woomp' ),
				'company'    => __( 'company', 'woomp' ),
				'donate'     => __( 'donate', 'woomp' ),
			)
		);

		// 個人發票選項
		if ( ! get_option( 'wc_woomp_ecpay_invoice_carrier_type' ) ) {
			update_option( 'wc_woomp_ecpay_invoice_carrier_type', array( '雲端發票', '手機代碼', '自然人憑證', '紙本發票' ) );
		}
		$type_option = array();
		foreach ( get_option( 'wc_woomp_ecpay_invoice_carrier_type' ) as $value ) {
			$type_option[ $value ] = $value;
		}

		$this->add_wc_field(
			'individual-invoice',
			'select',
			__( 'Individual Invoice Type', 'woomp' ),
			array( 'no-search' ),
			'invoice-label',
			$type_option,
		);

		// 自然人憑證與手機條碼 載具編號欄位
		$this->add_wc_field(
			'carrier-number',
			'text',
			__( 'Carrier Number', 'woomp' ),
			array( 'hide-option-field' ),
			'invoice-label',
			array()
		);

		// 公司統一編號欄位
		$this->add_wc_field(
			'company-name',
			'text',
			__( 'Company Name', 'woomp' ),
			array( 'hide-option-field' ),
			'invoice-label',
			array()
		);

		$this->add_wc_field(
			'taxid-number',
			'text',
			__( 'TaxID', 'woomp' ),
			array( 'hide-option-field' ),
			'invoice-label',
			array()
		);

		// 捐贈捐贈碼欄位
		$this->add_wc_field(
			'donate-number',
			'select',
			__( 'Donate Number', 'woomp' ),
			array( 'hide-option-field' ),
			'invoice-label',
			$this->get_donate_org(),
		);

	}

	private function add_wc_field( $name, $type, $label, $class, $label_class, $options, $placeholder = null ) {
		woocommerce_form_field(
			$name,
			array(
				'type'        => $type,
				'label'       => $label,
				'class'       => $class,
				'label_class' => $label_class,
				'options'     => $options,
				'placeholder' => $placeholder,
			),
		);
	}

	private function get_donate_org() {
		$orgs = array(
			'' => '請選擇',
		);
		if ( get_option( 'wc_woomp_ecpay_invoice_donate_org' ) ) {
			$org_strings = array_map( 'trim', explode( "\n", get_option( 'wc_woomp_ecpay_invoice_donate_org' ) ) );
			foreach ( $org_strings as $value ) {
				list($k, $v) = explode( '|', $value );
				$orgs[ $k ]  = $v;
			}
		} else {
			$orgs['25885'] = '伊甸社會福利基金會';
		}
		return $orgs;
	}

	/**
	 * 前端結帳頁面客製化欄位驗證
	 */
	public function set_invoice_field_validate() {
		// 如果選了自然人憑證，就要參加資料驗證。比對前 2 碼大寫英文，後 14 碼數字
		if ( $_POST['individual-invoice'] == '自然人憑證' && preg_match( '/^[A-Z]{2}\d{14}$/', $_POST['carrier-number'] ) == false ) {
			wc_add_notice( __( '<strong>電子發票 自然人憑證</strong> 請輸入前 2 位大寫英文與 14 位數字自然人憑證號碼' ), 'error' );
		}

		// 如果選了手機條碼，就要參加資料驗證。比對 7 位英數字
		if ( $_POST['individual-invoice'] == '手機代碼' && preg_match( '/^\/[A-Za-z0-9+-\.]{7}$/', $_POST['carrier-number'] ) == false ) {
			wc_add_notice( __( '<strong>電子發票 手機代碼</strong> 請輸入第 1 碼為「/」，後 7 碼為大寫英文、數字、「+」、「-」或「.」' ), 'error' );
		}

		// 如果選了公司，就要參加資料驗證。比對 8 位數字資料，如果失敗顯示錯誤訊息。
		if ( $_POST['invoice-type'] == 'company' && preg_match( '/^\d{8}$/', $_POST['taxid-number'] ) == false ) {
			wc_add_notice( __( '<strong>統一編號</strong> 請輸入 8 位數字組成統一編號' ), 'error' );
		}

		if ( $_POST['invoice-type'] == 'company' && preg_match( '/./s', $_POST['company-name'] ) == false ) {
			wc_add_notice( __( '<strong>公司名稱</strong> 為必填欄位' ), 'error' );
		}

		// 如果選了捐贈發票，就要參加資料驗證。比對 3~7 位數字資料，如果失敗顯示錯誤訊息。
		if ( $_POST['invoice-type'] == 'donate' && preg_match( '/^\d{3,7}$/', $_POST['donate-number'] ) == false ) {
			wc_add_notice( __( '<strong>捐贈碼</strong> 請輸入 3~7 位數字' ), 'error' );
		}

	}

	/**
	 * 發票資料寫入
	 */
	public function set_invoice_meta( $order_id ) {
		$order = wc_get_order( $order_id );

		foreach ( $order->get_items() as $item ) {
			$product_type = \WC_Product_Factory::get_product_type( $item->get_product_id() );
		}

		if ( '0' === $order->get_total() && strpos( $product_type, 'subscription' ) === false ) {
			return;
		}

		$invoice_data = array();
		// 新增發票開立類型
		if ( isset( $_POST['invoice-type'] ) ) {
			$invoice_data['_invoice_type'] = wp_unslash( $_POST['invoice-type'] );
		}
		// 新增個人發票選項
		if ( isset( $_POST['individual-invoice'] ) ) {
			$invoice_data['_invoice_individual'] = wp_unslash( $_POST['individual-invoice'] );
		} else {
			$invoice_data['_invoice_individual'] = false;
		}
		// 新增載具編號
		if ( isset( $_POST['carrier-number'] ) && ( $_POST['individual-invoice'] == '手機代碼' || $_POST['individual-invoice'] == '自然人憑證' ) ) {
			$invoice_data['_invoice_carrier'] = wp_unslash( $_POST['carrier-number'] );
		}
		// 新增公司名稱
		if ( isset( $_POST['company-name'] ) ) {
			$invoice_data['_invoice_company_name'] = wp_unslash( $_POST['company-name'] );
		}
		// 新增統一編號
		if ( isset( $_POST['taxid-number'] ) ) {
			$invoice_data['_invoice_tax_id'] = wp_unslash( $_POST['taxid-number'] );
		}
		// 新增捐贈碼
		if ( isset( $_POST['donate-number'] ) ) {
			$invoice_data['_invoice_donate'] = wp_unslash( $_POST['donate-number'] );
		}

		if ( count( $invoice_data ) > 0 ) {
			$order->update_meta_data( '_ecpay_invoice_data', $invoice_data );
			$order->save();
		}
	}

	private function get_cart_info( $type = null ) {

		if ( $type === 'product_type' ) {
			$product_type = '';
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$product      = wc_get_product( $cart_item['product_id'] );
				$product_type = $product->get_type();
			}

			return $product_type;
		}

		if ( $type === 'total' ) {
			return WC()->cart->total;
		}

	}

	/**
	 * 引入 JS
	 */
	public function enqueue_scripts() {
		if ( is_checkout() ) {

			wp_register_script( 'woomp_ecpay_invoice', ECPAYINVOICE_PLUGIN_URL . 'assets/js/checkout.js', array( 'jquery' ), '1.0.9', true );
			wp_localize_script(
				'woomp_ecpay_invoice',
				'woomp_ecpay_invoice_params',
				array(
					'product_type' => $this->get_cart_info( 'product_type' ),
					'cart_total'   => $this->get_cart_info( 'total' ),
				)
			);
			wp_enqueue_script( 'woomp_ecpay_invoice' );
		}
	}

}

Checkout::init();
