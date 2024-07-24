<?php
class WMP_ECPay_Gateway_Credit_Installment_12 extends RY_ECPay_Gateway_Base {

	public $payment_type = 'Credit';

	public function __construct() {

		$this->id                 = 'wmp_ecpay_credit_installment_12';
		$this->has_fields         = false;
		$this->order_button_text  = '綠界信用卡分期付款';
		$this->method_title       = '綠界信用卡（分十二期）';
		$this->method_description = '';
		$this->form_fields        = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/settings-ecpay-gateway-credit-installment.php';
		unset( $this->form_fields['number_of_periods'] );
		$this->init_settings();
		$this->title             = $this->get_option( 'title' );
		$this->description       = $this->get_option( 'description' );
		$this->min_amount        = (int) $this->get_option( 'min_amount', 0 );
		$this->number_of_periods = 12;

		parent::__construct();
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		$order->add_order_note( '使用綠界信用卡分期付款（分十二期）' );
		$order->save();
		wc_maybe_reduce_stock_levels( $order_id );
		wc_release_stock_for_order( $order );

		return [
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		];
	}

	public function is_available() {
		if ( 'yes' === $this->enabled && WC()->cart ) {
			$total = $this->get_order_total();

			if ( $total > 0 ) {
				if ( $this->min_amount > 0 && $total < $this->min_amount ) {
					return false;
				}
			}
		}

		return parent::is_available();
	}

	public function process_admin_options() {
		parent::process_admin_options();
	}
}
