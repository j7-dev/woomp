<?php
if ( ! class_exists( 'RY_ECPay_Shipping_Email_Customer_ATM_Transfer_Remind', false ) ) {
	class RY_ECPay_Shipping_Email_Customer_ATM_Transfer_Remind extends WC_Email {

		public function __construct() {
			$this->id             = 'ry_ecpay_shipping_customer_atm_transfer_remind';
			$this->customer_email = true;

			$this->title          = __( '綠界 ATM 櫃員機轉帳提醒', 'ry-woocommerce-tools' );
			$this->description    = __( '這是在商品經由綠界金流 ATM 櫃員機付款距離轉帳到期日前一天的通知。', 'ry-woocommerce-tools' );
			$this->template_base  = RY_WT_PLUGIN_DIR . 'templates/';
			$this->template_html  = 'emails/customer-atm-remind.php';
			$this->template_plain = 'emails/plain/customer-atm-remind.php';
			$this->placeholders   = [
				'{site_title}' => $this->get_blogname(),
			];

			// add_action( 'ry_ecpay_shipping_cvs_to_transporting_notification', array( $this, 'trigger' ), 10, 2 );

			parent::__construct();
		}

		public function get_default_subject() {
			return __( '您在 {site_title} 訂購的商品明天就要到期了，請儘速使用 ATM 轉帳繳費', 'ry-woocommerce-tools' );
		}

		public function get_default_heading() {
			return __( 'ATM 轉帳提醒', 'ry-woocommerce-tools' );
		}

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

		public function get_default_additional_content() {
			return __( 'We look forward to fulfilling your order soon.', 'woocommerce' );
		}
	}
}

return new RY_ECPay_Shipping_Email_Customer_ATM_Transfer_Remind();
