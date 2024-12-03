<?php

/**
 * 類 WMP_Shipped
 * 已出貨
 */
final class WMP_Shipped extends WC_Email { // phpcs:ignore

	/**
	 * 構造函數，初始化電郵屬性
	 */
	public function __construct() {
		$this->id             = 'wmp_shipped';
		$this->customer_email = true;

		$this->title          = __( '商品已出貨', 'ry-woocommerce-tools' );
		$this->description    = __( '這是在訂單狀態變成已出貨時通知訂購人。', 'ry-woocommerce-tools' );
		$this->template_base  = RY_WT_PLUGIN_DIR . 'templates/';
		$this->template_html  = 'emails/customer-cvs-transporting.php';
		$this->template_plain = 'emails/plain/customer-cvs-transporting.php';
		$this->placeholders   = [
			'{site_title}' => $this->get_blogname(),
		];

		parent::__construct();
	}

	/**
	 * 獲取默認的電郵主題
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( '您在 {site_title} 訂購的商品已出貨', 'ry-woocommerce-tools' );
	}

	/**
	 * 獲取默認的電郵標題
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( '商品出貨通知', 'ry-woocommerce-tools' );
	}

	/**
	 * 觸發電郵發送
	 *
	 * @param int            $order_id 訂單 ID
	 * @param WC_Order|false $order 訂單對象
	 */
	public function trigger( $order_id, $order = false ) {
		$this->setup_locale();

		if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( is_a( $order, 'WC_Order' ) ) {
			$this->object    = $order;
			$this->recipient = $this->object->get_billing_email();
		}

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	/**
	 * 獲取 HTML 格式的電郵內容
	 *
	 * @return string
	 */
	public function get_content_html() {
		$args = [
			'order'              => $this->object,
			'email_heading'      => $this->get_heading(),
			'additional_content' => $this->get_additional_content(),
			'sent_to_admin'      => false,
			'plain_text'         => false,
			'email'              => $this,
		];
		return wc_get_template_html( $this->template_html, $args, '', RY_WT_PLUGIN_DIR . 'templates/' );
	}

	/**
	 * 獲取純文本格式的電郵內容
	 *
	 * @return string
	 */
	public function get_content_plain() {
		$args = [
			'order'              => $this->object,
			'email_heading'      => $this->get_heading(),
			'additional_content' => $this->get_additional_content(),
			'sent_to_admin'      => false,
			'plain_text'         => true,
			'email'              => $this,
		];
		return wc_get_template_html( $this->template_plain, $args, '', RY_WT_PLUGIN_DIR . 'templates/' );
	}

	/**
	 * 獲取默認的附加內容
	 *
	 * @return string
	 */
	public function get_default_additional_content() {
		return __( 'We look forward to fulfilling your order soon.', 'woocommerce' );
	}
}

// 返回類的實例
return new WMP_Shipped();
