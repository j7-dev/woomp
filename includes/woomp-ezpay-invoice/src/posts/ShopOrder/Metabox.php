<?php

namespace WOOMPEZPAYINVOICE\ShopOrder;

use ODS\Metabox;

defined( 'ABSPATH' ) || exit;


class Field {


	private $metabox;

	public static function register() {
		$class = new self();
		add_action( 'current_screen', [ $class, 'set_metabox' ] );
	}

	/**
	 * 建立發票資訊欄位
	 */
	public function set_metabox() {

		if ( ! \wc_string_to_bool( \get_option( 'wc_woomp_enabled_ezpay_invoice' ) ) ) {
			return;
		}

		if ( ! isset( $_GET['post'] ) ) {
			return;
		}

		$screen = \get_current_screen();

		$id              = (int) $_GET['post'];
		$is_subscription = 'shop_subscription' === $screen->id;
		if ($is_subscription) {
			$subscription = \wcs_get_subscription($id);
			$id           = $subscription->get_parent_id();
		}

		$order = \wc_get_order( $id );

		if ( ! $order ) {
			return;
		}

		$product_type = '';

		foreach ( $order->get_items() as $item ) {
			/**
			 * @var \WC_Order_Item_Product $item
			 */
			$product_type = \WC_Product_Factory::get_product_type( $item->get_product_id() );
		}

		if ( '0' === $order->get_total() && strpos( $product_type, 'subscription' ) === false ) {
			return;
		}

		$this->metabox = new Metabox(
			[
				'id'       => 'ezpay_invoice',
				'title'    => __( '藍新 ezPay 電子發票', 'woomp' ),
				'screen'   => [ 'shop_order', 'shop_subscription' ],
				'context'  => 'side',
				'priority' => 'default',
			]
		);

		if ( ! $order->get_meta( '_ezpay_invoice_data' ) ) {
			$order->update_meta_data( '_ezpay_invoice_data', [] );
			$order->save();
		}

		$ezpay_invoice_data = (array) $order->get_meta( '_ezpay_invoice_data' );

		$_invoice_type         = $ezpay_invoice_data['_ezpay_invoice_type'] ?? '';
		$_invoice_individual   = $ezpay_invoice_data['_ezpay_invoice_individual'] ?? '';
		$_invoice_carrier      = $ezpay_invoice_data['_ezpay_invoice_carrier'] ?? '';
		$_invoice_company_name = $ezpay_invoice_data['_ezpay_invoice_company_name'] ?? '';
		$_invoice_tax_id       = $ezpay_invoice_data['_ezpay_invoice_tax_id'] ?? '';
		$_invoice_donate       = $ezpay_invoice_data['_ezpay_invoice_donate'] ?? '';

		// output

		$disable_style = $order->get_meta( '_ezpay_invoice_number' ) ? 'pointer-events:none;border:0;appearance:none;background-image:none;background-color:#efefef;' : '';

		ob_start();

		printf(
		/*html*/'
		<p><strong>%1$s</strong></p>
		<select name="_ezpay_invoice_type" style="display:block;width:100%%;margin-top:-8px;%2$s">
			<option value="individual" %3$s>%4$s</option>
			<option value="company" %5$s>%6$s</option>
			<option value="donate" %7$s>%8$s</option>
		</select>
		',
			__( 'Invoice Type', 'woomp' ),
		$disable_style,
		\selected( $_invoice_type, 'individual', false ),
		\__( 'individual', 'woomp' ),
		\selected( $_invoice_type, 'company', false ),
		\__( 'company', 'woomp' ),
		\selected( $_invoice_type, 'donate', false ),
		\__( 'donate', 'woomp' )
		);

		// 顯示個人發票類型

		$options = '';
		if ( \get_option( 'wc_woomp_ezpay_invoice_carrier_type' ) ) {
			foreach ( \get_option( 'wc_woomp_ezpay_invoice_carrier_type' ) as $key => $value ) {
				$options .= sprintf(
				/*html*/'
				<option value="%1$s" %2$s>%3$s</option>
				',
					$value,
				\selected( $_invoice_individual, $value, false ),
				$value
				);
			}
		}

		printf(
		/*html*/'
		<div id="ezPayInvoiceIndividual" style="display:none;">
			<p><strong>%1$s</strong></p>
			<select name="_ezpay_invoice_individual" style="display:block;width:100%%;margin-top:-8px;%2$s">
				%3$s
			</select>
		</div>
		',
			__( 'Individual Invoice Type', 'woomp' ),
		$disable_style,
		$options
		);

		// 顯示載具編號
		printf(
		/*html*/'
		<div id="ezPayInvoiceCarrier" style="display:none;">
			<p><strong>%1$s</strong></p>
			<p><input type="text" name="_ezpay_invoice_carrier" value="%2$s" style="margin-top:-10px;width:100%%;%3$s" /><p>
		</div>
		',
			__( 'Carrier Number', 'woomp' ),
		$_invoice_carrier,
		$disable_style
		);

		// 顯示公司名稱
		printf(
		/*html*/'
		<div id="ezPayInvoiceCompanyName" style="display:none;">
			<p><strong>%1$s</strong></p>
			<p><input type="text" name="_ezpay_invoice_company_name" value="%2$s" style="margin-top:-10px;width:100%%;%3$s" /><p>
		</div>
		',
			__( 'Company Name', 'woomp' ),
		$_invoice_company_name,
		$disable_style
		);

		// 顯示統一編號
		printf(
		/*html*/'
		<div id="ezPayInvoiceTaxId" style="display:none;">
			<p><strong>%1$s</strong></p>
			<p><input type="text" name="_ezpay_invoice_tax_id" value="%2$s" style="margin-top:-10px;width:100%%;%3$s" /><p>
		</div>
		',
			__( 'TaxID', 'woomp' ),
		$_invoice_tax_id,
		$disable_style
		);

		// 顯示捐贈碼
		printf(
		/*html*/'
		<div id="ezPayInvoiceDonate" style="display:none;">
			<p><strong>%1$s</strong></p>
			<p><input type="text" name="_ezpay_invoice_donate" value="%2$s" style="margin-top:-10px;width:100%%;%3$s" /><p>
		</div>
		',
			__( 'Donate Number', 'woomp' ),
		$_invoice_donate,
		$disable_style
		);

		echo $this->get_invoice_button( $id, $is_subscription );

		$output = ob_get_clean();

		$this->metabox->addHtml(
			[
				'id'   => 'ezpay_invoice_section',
				'html' => $output ,
			],
		);
	}

	/**
	 * 建立發票開立按鈕
	 *
	 * @param int  $order_id 訂單ID
	 * @param bool $is_subscription 是否為訂閱的編輯內頁
	 * @return string
	 */
	private function get_invoice_button( $order_id, $is_subscription = false ): string {

		$order = \wc_get_order( $order_id );

		if ($is_subscription) {
			return sprintf(
				/*html*/'
				<button class="button save_order button-primary" id="btnUpdateInvoiceDataEzPay" type="button" value="%1$s" disabled>%2$s</button>
				',
				$order_id,
			'更新上層訂單發票資料'
				);
		}

		echo '<div style="display:flex;justify-content:space-between;margin-top:0.5rem;">';

		// 產生按鈕，傳送 order id 給ajax js
		if ( ! $order->get_meta( '_ezpay_invoice_number' ) ) {
			printf(
				/*html*/'
				<button class="button btnGenerateInvoiceEzPay" type="button" value="%1$s">開立發票</button>
				',
				$order_id
				);

			printf(
			/*html*/'
			<button class="button save_order button-primary" id="btnUpdateInvoiceDataEzPay" type="button" value="%1$s" disabled>%2$s</button>
			',
			$order_id,
				'更新發票資料'
			);
		} else {
			printf(
			/*html*/'
			<button class="button btnInvalidInvoiceEzPay" type="button" value="%1$s">作廢發票</button>
			',
			$order_id
			);
		}

		echo '</div>';

		return ob_get_clean();
	}
}

Field::register();
