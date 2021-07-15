<?php
if (!class_exists('WC_Settings_RY_Wei', false)) {
    class WC_Settings_RY_Wei extends WC_Settings_Page
    {
        public function __construct()
        {
            $this->id = 'rytools';
            $this->label = __('RY Tools', 'ry-woocommerce-ecpay-invoice');

            parent::__construct();
        }

        public function get_sections()
        {
            $sections = [
                'ry_key' => __('License key', 'ry-woocommerce-ecpay-invoice')
            ];

            return apply_filters('woocommerce_get_sections_' . $this->id, $sections);
        }

        public function output()
        {
            global $current_section, $hide_save_button;

            if ($current_section == '') {
                $current_section = 'ry_key';
            }

            if (apply_filters('ry_setting_section_' . $current_section, true)) {
                $settings = $this->get_settings($current_section);
                WC_Admin_Settings::output_fields($settings);
            } else {
                do_action('ry_setting_section_ouput_' . $current_section);
            }
        }

        public function save()
        {
            global $current_section;

            if ($current_section == '') {
                $current_section = 'ry_key';
            }

            if (apply_filters('ry_setting_section_' . $current_section, true)) {
                $settings = $this->get_settings($current_section);
                WC_Admin_Settings::save_fields($settings);
            }

            if ($current_section) {
                do_action('woocommerce_update_options_' . $this->id . '_' . $current_section);
            }
        }

        public function get_settings($current_section = '')
        {
            $settings = [];

            if ($current_section == '') {
                $current_section = 'ry_key';
            }

            return apply_filters('woocommerce_get_settings_' . $this->id, $settings, $current_section);
        }
    }
}

return new WC_Settings_RY_Wei();
