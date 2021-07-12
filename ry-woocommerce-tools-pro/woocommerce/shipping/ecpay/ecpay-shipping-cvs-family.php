<?php
class RY_ECPay_Shipping_CVS_Family_Pro extends RY_ECPay_Shipping_CVS_Family
{
    public function __construct($instance_id = 0)
    {
        $this->instance_form_fields = include RY_WT_PLUGIN_DIR . 'woocommerce/shipping/ecpay/includes/settings-ecpay-shipping-base.php';

        $this->instance_form_fields['cost_requires']['options']['min_amount_except_discount'] = __('A minimum order amount ( except discount and tex )', 'ry-woocommerce-tools-pro');
        $this->instance_form_fields['cost_requires']['options']['min_amount_except_discount_or_coupon'] = __('A minimum order amount OR a coupon ( except discount and tex )', 'ry-woocommerce-tools-pro');
        $this->instance_form_fields['cost_requires']['options']['min_amount_except_discount_and_coupon'] = __('A minimum order amount AND a coupon ( except discount and tex )', 'ry-woocommerce-tools-pro');

        parent::__construct($instance_id);
    }

    public function calculate_shipping($package = [])
    {
        $rate = [
            'id' => $this->get_rate_id(),
            'label' => $this->title,
            'cost' => $this->cost,
            'package' => $package,
            'meta_data' => [
                'no_count' => 1
            ]
        ];

        $has_coupon = $this->check_has_coupon($this->cost_requires, ['coupon', 'min_amount_or_coupon', 'min_amount_and_coupon', 'min_amount_except_discount_or_coupon', 'min_amount_except_discount_and_coupon']);
        $has_min_amount = $this->check_has_min_amount($this->cost_requires, ['min_amount', 'min_amount_or_coupon', 'min_amount_and_coupon']);
        $has_min_amount_original = $this->check_has_min_amount($this->cost_requires, ['min_amount_except_discount', 'min_amount_except_discount_or_coupon', 'min_amount_except_discount_and_coupon'], true);

        switch ($this->cost_requires) {
            case 'coupon':
                $set_cost_zero = $has_coupon;
                break;
            case 'min_amount':
                $set_cost_zero = $has_min_amount;
                break;
            case 'min_amount_or_coupon':
                $set_cost_zero = $has_min_amount || $has_coupon;
                break;
            case 'min_amount_and_coupon':
                $set_cost_zero = $has_min_amount && $has_coupon;
                break;
            case 'min_amount_except_discount':
                $set_cost_zero = $has_min_amount_original;
                break;
            case 'min_amount_except_discount_or_coupon':
                $set_cost_zero = $has_min_amount_original || $has_coupon;
                break;
            case 'min_amount_except_discount_and_coupon':
                $set_cost_zero = $has_min_amount_original && $has_coupon;
                break;
            default:
                $set_cost_zero = false;
                break;
        }

        if ($set_cost_zero) {
            $rate['cost'] = 0;
        }

        if ($this->weight_plus_cost > 0) {
            $total_weight = WC()->cart->get_cart_contents_weight();
            if ($total_weight > 0) {
                $rate['meta_data']['no_count'] = (int) ceil($total_weight / $this->weight_plus_cost);
                $rate['cost'] *= $rate['meta_data']['no_count'];
            }
        }

        $this->add_rate($rate);
        do_action('woocommerce_' . $this->id . '_shipping_add_rate', $this, $rate);
    }
}
