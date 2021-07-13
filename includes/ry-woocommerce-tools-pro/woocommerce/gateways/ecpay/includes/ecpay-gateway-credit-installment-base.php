<?php
class RY_ECPay_Gateway_Credit_Installment_Base extends RY_ECPay_Gateway_Base
{
    public $payment_type = 'Credit';

    public function __construct()
    {
        $this->has_fields = false;
        $this->method_description = '';

        $this->form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/gateways/ecpay/includes/settings-ecpay-gateway-credit-installment.php';
        unset($this->form_fields['number_of_periods']);
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->min_amount = (int) $this->get_option('min_amount', 0);

        parent::__construct();
    }

    public function is_available()
    {
        if ('yes' == $this->enabled && WC()->cart) {
            $total = $this->get_order_total();

            if ($total > 0) {
                if ($this->min_amount > 0 and $total < $this->min_amount) {
                    return false;
                }
            }
        }

        return parent::is_available();
    }

    public function process_admin_options()
    {
        parent::process_admin_options();
    }
}
