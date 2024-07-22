<?php
class WMP_NewebPay_Gateway_Credit_Installment_30 extends RY_NewebPay_Gateway_Base {

	public $payment_type = 'InstFlag';

	public function __construct() {

		$this->id                 = 'wmp_newebpay_credit_installment_30';
		$this->has_fields         = false;
		$this->order_button_text  = '藍新信用卡分期付款';
		$this->method_title       = '藍新信用卡（分三十期）';
		$this->method_description = '';
		$this->form_fields        = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/newebpay/includes/settings-newebpay-gateway-credit-installment.php';
		unset( $this->form_fields['number_of_periods'] );
		$this->init_settings();
		$this->title             = $this->get_option( 'title' );
		$this->description       = $this->get_option( 'description' );
		$this->min_amount        = (int) $this->get_option( 'min_amount', 0 );
		$this->number_of_periods = 30;

		parent::__construct();
	}

	public function is_available() {
		if ( 'yes' === $this->enabled && WC()->cart ) {
			if ( empty( $this->number_of_periods ) ) {
				return false;
			}
			$total = $this->get_order_total();

			if ( $total > 0 ) {
				if ( $this->min_amount > 0 && $total < $this->min_amount ) {
					return false;
				}
			}
		}

		return parent::is_available();
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		$order->add_order_note( '使用藍新信用卡分期付款（分三十期）' );

		if ( isset( $_POST['newebpay_number_of_periods'] ) ) {
			$order->update_meta_data( '_newebpay_payment_number_of_periods', (int) $_POST['newebpay_number_of_periods'] );
		}
		$order->save();
		wc_maybe_reduce_stock_levels( $order_id );
		wc_release_stock_for_order( $order );

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);
	}

	public function process_admin_options() {
		parent::process_admin_options();
	}
}
