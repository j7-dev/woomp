<?php
class RY_ECPay_Gateway_Credit_Installment_3 extends RY_ECPay_Gateway_Credit_Installment_Base
{
    public function __construct()
    {
        $this->id = 'ry_ecpay_credit_installment_3';
        $this->order_button_text = __('Pay via Credit(3 installment)', 'ry-woocommerce-tools-pro');
        $this->method_title = __('ECPay Credit(3 installment)', 'ry-woocommerce-tools-pro');

        $this->number_of_periods = 3;

        parent::__construct();
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $order->add_order_note(__('Pay via ECPay Credit(3 installment)', 'ry-woocommerce-tools-pro'));
        $order->save();
        wc_maybe_reduce_stock_levels($order_id);
        wc_release_stock_for_order($order);

        return [
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        ];
    }
}
