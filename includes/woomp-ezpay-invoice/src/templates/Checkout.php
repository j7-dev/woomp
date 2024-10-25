<?php
namespace WOOMPEZPAYINVOICE\Templates;

defined( 'ABSPATH' ) || exit;

class Checkout {


	public static function init() {
		$class = new self();
		add_action( 'wp_enqueue_scripts', [ $class, 'enqueue_scripts' ] );
		add_action( 'woocommerce_after_checkout_billing_form', [ $class, 'set_invoice_field' ] );
		add_action( 'woocommerce_checkout_process', [ $class, 'set_invoice_field_validate' ] );
		add_action( 'woocommerce_checkout_update_order_meta', [ $class, 'set_invoice_meta' ] );
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
			'ezpay-invoice-type',
			'select',
			__( 'Invoice Type', 'woomp' ),
			[],
			'invoice-label',
			[ // 發票開立選項
				'individual' => __( 'individual', 'woomp' ),
				'company'    => __( 'company', 'woomp' ),
				'donate'     => __( 'donate', 'woomp' ),
			]
		);

		// 個人發票選項
		if ( ! get_option( 'wc_woomp_ezpay_invoice_carrier_type' ) ) {
			update_option( 'wc_woomp_ezpay_invoice_carrier_type', [ '手機條碼', '自然人憑證', 'ezPay 電子發票載具' ] );
		}
		$type_option = [];
		foreach ( get_option( 'wc_woomp_ezpay_invoice_carrier_type' ) as $value ) {
			$type_option[ $value ] = $value;
		}

		$this->add_wc_field(
			'ezpay-individual-invoice',
			'select',
			__( 'Individual Invoice Type', 'woomp' ),
			[ 'no-search' ],
			'invoice-label',
			$type_option,
		);

		// 自然人憑證與手機條碼 載具編號欄位
		$this->add_wc_field(
			'ezpay-carrier-number',
			'text',
			__( 'Carrier Number', 'woomp' ),
			[ 'hide-option-field' ],
			'invoice-label',
			[]
		);

		$this->add_wc_field(
			'ezpay-taxid-number',
			'text',
			__( 'TaxID', 'woomp' ),
			[ 'hide-option-field' ],
			'invoice-label',
			[]
		);

		// 公司統一編號欄位
		$this->add_wc_field(
			'ezpay-company-name',
			'text',
			__( 'Company Name', 'woomp' ),
			[ 'hide-option-field' ],
			'invoice-label',
			[]
		);

		// 捐贈捐贈碼欄位
		$this->add_wc_field(
			'ezpay-donate-number',
			'select',
			__( 'Donate Number', 'woomp' ),
			[ 'hide-option-field' ],
			'invoice-label',
			$this->get_donate_org(),
		);
	}

	private function add_wc_field( $name, $type, $label, $class, $label_class, $options, $placeholder = null ) {
		woocommerce_form_field(
			$name,
			[
				'type'        => $type,
				'label'       => $label,
				'class'       => $class,
				'label_class' => $label_class,
				'options'     => $options,
				'placeholder' => $placeholder,
			],
		);
	}

	private function get_donate_org() {
		$orgs = [
			'' => '請選擇',
		];
		if ( get_option( 'wc_woomp_ezpay_invoice_donate_org' ) ) {
			$org_strings = array_map( 'trim', explode( "\n", get_option( 'wc_woomp_ezpay_invoice_donate_org' ) ) );
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
		$fields = [
			'individual_invoice' => 'ezpay-individual-invoice',
			'carrier_number'     => 'ezpay-carrier-number',
			'taxid_number'       => 'ezpay-taxid-number',
			'company_name'       => 'ezpay-company-name',
			'donate_number'      => 'ezpay-donate-number',
			'invoice_type'       => 'ezpay-invoice-type',
		];

		foreach ($fields as $key => $field) {
			$$key = $_POST[ $field ] ?? ''; // phpcs:ignore
		}

		// 如果選了自然人憑證，就要參加資料驗證。比對前 2 碼大寫英文，後 14 碼數字
		if ( $individual_invoice === '自然人憑證' && preg_match( '/^[A-Z]{2}\d{14}$/', $carrier_number ) == false ) {
			\wc_add_notice( __( '<strong>電子發票 自然人憑證</strong> 請輸入前 2 位大寫英文與 14 位數字自然人憑證號碼' ), 'error' );
		}

		// 如果選了手機條碼，就要參加資料驗證。比對 7 位英數字
		if ( $individual_invoice === '手機條碼' && preg_match( '/^\/[A-Za-z0-9+-\.]{7}$/', $carrier_number ) == false ) {
			\wc_add_notice( __( '<strong>電子發票 手機條碼</strong> 請輸入第 1 碼為「/」，後 7 碼為大寫英文、數字、「+」、「-」或「.」' ), 'error' );
		}

		// 如果選了公司，就要參加資料驗證。比對 8 位數字資料，如果失敗顯示錯誤訊息。
		if ( $invoice_type === 'company' && preg_match( '/^\d{8}$/', $taxid_number ) == false ) {
			\wc_add_notice( __( '<strong>統一編號</strong> 請輸入 8 位數字組成統一編號' ), 'error' );
		}

		if ( $invoice_type === 'company' && preg_match( '/./s', $company_name ) == false ) {
			\wc_add_notice( __( '<strong>公司名稱</strong> 為必填欄位' ), 'error' );
		}

		// 如果選了捐贈發票，就要參加資料驗證。比對 3~7 位數字資料，如果失敗顯示錯誤訊息。
		if ( $invoice_type === 'donate' && preg_match( '/^\d{3,7}$/', $donate_number ) == false ) {
			\wc_add_notice( __( '<strong>捐贈碼</strong> 請輸入 3~7 位數字' ), 'error' );
		}
	}

	/**
	 * 發票資料寫入
	 */
	public function set_invoice_meta( $order_id ) {
		$order = \wc_get_order( $order_id );

		foreach ( $order->get_items() as $item ) {
			/**
			 * @var \WC_Order_Item_Product $item
			 */
			$product_type = \WC_Product_Factory::get_product_type( $item->get_product_id() );
		}

		// 如果總金額為 0 且非訂閱商品，則不開立發票
		if ( '0' === $order->get_total() && strpos( $product_type, 'subscription' ) === false ) {
			return;
		}

		// TODO 欄位轉換
		// 未來再統一欄位，需要向後兼容，欄位修改
		$ezpay_invoice_type  = $_POST['ezpay-invoice-type'] ?? '';
		$invoice_data        = [
			'_ezpay_invoice_type' => $ezpay_invoice_type,
		];
		$invoice_data_fields = [
			'_ezpay_invoice_individual'   => 'ezpay-individual-invoice',
			'_ezpay_invoice_carrier'      => 'ezpay-carrier-number',
			'_ezpay_invoice_company_name' => 'ezpay-company-name',
			'_ezpay_invoice_tax_id'       => 'ezpay-taxid-number',
			'_ezpay_invoice_donate'       => 'ezpay-donate-number',
		];

		// phpcs:disable
		foreach ( $invoice_data_fields as $key => $field ) {
			if(!isset( $_POST[ $field ])) {
				continue;
			}

			if('individual' === $ezpay_invoice_type) {
				$allow_fields = ['ezpay-individual-invoice', 'ezpay-carrier-number'];
				if(in_array($field, $allow_fields)) {
					$value                = \wp_unslash( $_POST[ $field ] ?? '' );
					$invoice_data[ $key ] = $value;
			}
			}

			if('company' === $ezpay_invoice_type) {
				$allow_fields = ['ezpay-company-name', 'ezpay-taxid-number'];
				if(in_array($field, $allow_fields)) {
						$value                = \wp_unslash( $_POST[ $field ] ?? '' );
						$invoice_data[ $key ] = $value;
				}
			}

			if('donate' === $ezpay_invoice_type) {
				$allow_fields = ['ezpay-donate-number'];
				if(in_array($field, $allow_fields)) {
					$value                = \wp_unslash( $_POST[ $field ] ?? '' );
					$invoice_data[ $key ] = $value;
				}
			}
		}
		// phpcs:enable

		if('ezPay 電子發票載具' === $invoice_data['_ezpay_invoice_individual']) {
			unset($invoice_data['_ezpay_invoice_carrier']);
		}



		if ( count( $invoice_data ) > 0 ) {
			$order->update_meta_data( '_ezpay_invoice_data', $invoice_data );
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

			wp_register_script( 'woomp_ezpay_invoice', EZPAYINVOICE_PLUGIN_URL . 'assets/js/checkout.js', [ 'jquery' ], '1.0.10', true );
			wp_localize_script(
				'woomp_ezpay_invoice',
				'woomp_ezpay_invoice_params',
				[
					'product_type' => $this->get_cart_info( 'product_type' ),
					'cart_total'   => $this->get_cart_info( 'total' ),
				]
			);
			wp_enqueue_script( 'woomp_ezpay_invoice' );
		}
	}
}

Checkout::init();
