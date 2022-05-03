<?php
if ( ! class_exists( 'RY_ECPay_Shipping_Email_Customer_CVS_Get_Expired', false ) ) {
	class RY_ECPay_Shipping_Email_Customer_CVS_Get_Expired extends WC_Email {

		public function __construct() {
			$this->id             = 'ry_ecpay_shipping_customer_cvs_get_expired';
			$this->customer_email = true;

			$this->title          = __( '超商取貨到期', 'ry-woocommerce-tools' );
			$this->description    = __( '這是在商品經由綠界物流到店取貨到期的通知。', 'ry-woocommerce-tools' );
			$this->template_base  = RY_WT_PLUGIN_DIR . 'templates/';
			$this->template_html  = 'emails/customer-cvs-store.php';
			$this->template_plain = 'emails/plain/customer-cvs-store.php';
			$this->placeholders   = array(
				'{site_title}' => $this->get_blogname(),
			);

			add_action( 'ry_ecpay_shipping_cvs_get_expired_notification', array( $this, 'trigger' ), 10, 2 );

			parent::__construct();
		}

		public function get_default_subject() {
			return __( '您在 {site_title} 訂購的商品今天到期了，請儘速前往取貨', 'ry-woocommerce-tools' );
		}

		public function get_default_heading() {
			return __( '超商取貨逾期提醒', 'ry-woocommerce-tools' );
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
			$args = array(
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => false,
				'email'              => $this,
			);
			return wc_get_template_html( $this->template_html, $args, '', RY_WT_PLUGIN_DIR . 'templates/' );
		}

		public function get_content_plain() {
			$args = array(
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => true,
				'email'              => $this,
			);
			return wc_get_template_html( $this->template_plain, $args, '', RY_WT_PLUGIN_DIR . 'templates/' );
		}

		public function get_default_additional_content() {
			return __( 'We look forward to fulfilling your order soon.', 'woocommerce' );
		}
	}
}

return new RY_ECPay_Shipping_Email_Customer_CVS_Get_Expired();
