<?php

namespace WOOMPECPAYINVOICE\ShopOrder;

use ODS\Metabox;

defined( 'ABSPATH' ) || exit;


class Field {


	private $metabox;

	public static function register() {
		$class = new self();
		\add_action( 'current_screen', [ $class, 'set_metabox' ] );
	}

	/**
	 * 建立發票資訊欄位
	 */
	public function set_metabox() {

		if ( ! \wc_string_to_bool( \get_option( 'wc_woomp_enabled_ecpay_invoice' ) ) ) {
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
			$product_type = \WC_Product_Factory::get_product_type( $item->get_product_id() );
		}

		if ( '0' === $order->get_total() && strpos( $product_type, 'subscription' ) === false ) {
			return;
		}

		$this->metabox = new Metabox(
			[
				'id'       => 'ecpay_invoice',
				'title'    => __( '綠界電子發票(好用版)', 'woomp' ),
				'screen'   => [ 'shop_order', 'shop_subscription' ],
				'context'  => 'side',
				'priority' => 'default',
			]
		);

		if ( ! $order->get_meta( '_ecpay_invoice_data' ) ) {
			$order->update_meta_data( '_ecpay_invoice_data', [] );
			$order->save();
		}

		$ecpay_invoice_data = (array) $order->get_meta( '_ecpay_invoice_data' );

		$_invoice_type         = $ecpay_invoice_data['_invoice_type'] ?? '';
		$_invoice_individual   = $ecpay_invoice_data['_invoice_individual'] ?? '';
		$_invoice_carrier      = $ecpay_invoice_data['_invoice_carrier'] ?? '';
		$_invoice_company_name = $ecpay_invoice_data['_invoice_company_name'] ?? '';
		$_invoice_tax_id       = $ecpay_invoice_data['_invoice_tax_id'] ?? '';
		$_invoice_donate       = $ecpay_invoice_data['_invoice_donate'] ?? '';

		// output
		ob_start();

		printf(
		/*html*/'
		<p><strong>%1$s</strong></p>
		<select name="_invoice_type" style="display:block;width:100%%;margin-top:-8px;">
			<option value="individual" %2$s>%3$s</option>
			<option value="company" %4$s>%5$s</option>
			<option value="donate" %6$s>%7$s</option>
		</select>
		',
			\__( 'Invoice Type', 'woomp' ),
			\selected( $_invoice_type, 'individual', false ),
			\__( 'individual', 'woomp' ),
			\selected( $_invoice_type, 'company', false ),
			\__( 'company', 'woomp' ),
			\selected( $_invoice_type, 'donate', false ),
			\__( 'donate', 'woomp' )
		);

		// 顯示個人發票類型
		if ( $_invoice_individual >= 0 ) {
			$options = '';
			if ( \get_option( 'wc_woomp_ecpay_invoice_carrier_type' ) ) {
				foreach ( \get_option( 'wc_woomp_ecpay_invoice_carrier_type' ) as $key => $value ) {
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
			<div id="invoiceIndividual" style="display:none">
				<p><strong>%1$s</strong></p>
				<select name="_invoice_individual" style="display:block;width:100%%;margin-top:-8px;">
					%2$s
				</select>
			</div>
			',
				\__( 'Individual Invoice Type', 'woomp' ),
			$options
			);
		}

		// 顯示載具編號
		printf(
		/*html*/'
		<div id="invoiceCarrier" style="display:none">
			<p><strong>%1$s</strong></p>
			<p><input type="text" name="_invoice_carrier" value="%2$s" style="margin-top:-10px;width:100%%;" /><p>
		</div>
		',
			\__( 'Carrier Number', 'woomp' ),
			$_invoice_carrier
		);

		// 顯示公司名稱
		printf(
		/*html*/'
		<div id="invoiceCompanyName" style="display:none">
			<p><strong>%1$s</strong></p>
			<p><input type="text" name="_invoice_company_name" value="%2$s" style="margin-top:-10px;width:100%%;" /><p>
		</div>
		',
			\__( 'Company Name', 'woomp' ),
			$_invoice_company_name
		);

		// 顯示統一編號
		printf(
		/*html*/'
		<div id="invoiceTaxId" style="display:none">
			<p><strong>%1$s</strong></p>
			<p><input type="text" name="_invoice_tax_id" value="%2$s" style="margin-top:-10px;width:100%%;" /><p>
		</div>
		',
			\__( 'TaxID', 'woomp' ),
			$_invoice_tax_id
		);

		// 顯示捐贈碼
		printf(
		/*html*/'
		<div id="invoiceDonate" style="display:none">
			<p><strong>%1$s</strong></p>
			<p><input type="text" name="_invoice_donate" value="%2$s" style="margin-top:-10px;width:100%%;" /><p>
		</div>
		',
			\__( 'Donate Number', 'woomp' ),
			$_invoice_donate
		);

		echo $this->get_invoice_button( $id, $is_subscription );

		$output = ob_get_clean();

		$this->metabox->addHtml(
			[
				'id'   => 'ecpay_invoice_section',
				'html' => $output,
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
				<button class="button save_order button-primary" id="btnUpdateInvoiceData" type="button" value="%1$s">%2$s</button>
				',
					(int) $_GET['post'], // phpcs:ignore
					'更新訂閱發票資料'
			);
		}

		ob_start();

		echo '<div style="display:flex;justify-content:space-between;margin-top:0.5rem;">';

		// 產生按鈕，傳送 order id 給ajax js
		if ( empty( $order->get_meta( '_ecpay_invoice_number' ) ) ) {
			printf(
					/*html*/'
					<button class="button btnGenerateInvoice" type="button" value="%1$s">開立發票</button>
					',
						$order_id
				);

			printf(
				/*html*/'
				<button class="button save_order button-primary" id="btnUpdateInvoiceData" type="button" value="%1$s" disabled>%2$s</button>
				',
					$order_id,
					'更新發票資料'
			);
		} else {
			printf(
			/*html*/'
			<button class="button btnInvalidInvoice" type="button" value="%1$s">%2$s</button>
			',
				$order_id,
				'作廢發票'
			);
		}

		echo '</div>';

		return ob_get_clean();
	}
}

Field::register();
