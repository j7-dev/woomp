<?php

use \MGC\Logger\Logger;

class Woomp_Setting {

    /**
     * Bootstraps the class and hooks required actions & filters.
     *
     */
    public static function init() {
        add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
        add_action( 'woocommerce_settings_tabs_woomp_setting', __CLASS__ . '::settings_tab' );
        add_action( 'woocommerce_update_options_woomp_setting', __CLASS__ . '::update_settings' );
        add_action( 'admin_head', __CLASS__ . '::set_checkbox_toggle');
    }   
    
    /**
     * Add a new settings tab to the WooCommerce settings tabs array.
     *
     * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
     * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
     */
    public static function add_settings_tab( $settings_tabs ) {
        $settings_tabs['woomp_setting'] = __( '好用版 Woo', 'woomp' );
        return $settings_tabs;
    }


    /**
     * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
     *
     * @uses woocommerce_admin_fields()
     * @uses self::get_settings()
     */
    public static function settings_tab() {
        woocommerce_admin_fields( self::get_settings() );
    }


    /**
     * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     * @uses self::get_settings()
     */
    public static function update_settings() {
        woocommerce_update_options( self::get_settings() );
    }


    /**
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     *
     * @return array Array of settings for @see woocommerce_admin_fields() function.
     */
    public static function get_settings() {

        $settings = array(
            'section_title' => array(
                'name'     => __( '好用版 Woo 設定', 'woomp' ),
                'type'     => 'title',
                'desc'     => '',
                'id'       => 'wc_woomp_setting_section_title'
            ),
            'replace' => array(
                'name'     => __( '一頁結帳模式', 'woomp' ),
                'type'     => 'checkbox',
                'desc'     => __( '將原本兩段式結帳改成一頁式結帳，並改變結帳順序為 「選物流 -> 選金流 -> 填結帳欄位」，以適應超商取貨等物流模式', 'woomp' ),
                'id'       => 'wc_woomp_setting_replace',
                'class'    => 'toggle',
                'default'  => 'yes',
                'desc_tip' => true,
            ),
            'billing_country_pos' => array(
                'name'  => __( '結帳頁國家欄位置頂', 'woomp' ),
                'type'  => 'checkbox',
                'desc'  => __( '若需運送至多個國家，建議開啟此選項，將國家欄位移至物流選項之前', 'woomp' ),
                'id'    => 'wc_woomp_setting_billing_country_pos',
                'class' => 'toggle',
                'desc_tip' => true,
                'default'  => 'yes',
                'std' => 'yes'
            ),
            'tw_address' => array(
                'name'  => __( '縣市/鄉鎮市下拉式選單', 'woomp' ),
                'type'  => 'checkbox',
                'desc'  => __( '開啟此選項套用結帳中的台灣地址下拉選單', 'woomp' ),
                'id'    => 'wc_woomp_setting_tw_address',
                'class' => 'toggle',
                'desc_tip' => true,
                'default'  => 'yes',
                'std' => 'yes'
            ),
            'one_line_address' => array(
                'name'  => __( '訂單地址欄位整併', 'woomp' ),
                'type'  => 'checkbox',
                'desc'  => __( '開啟此選項套用後台訂單管理地址欄位整合為一行', 'woomp' ),
                'id'    => 'wc_woomp_setting_one_line_address',
                'class' => 'toggle',
                'desc_tip' => true,
                'default'  => 'yes',
                'std' => 'yes'
            ),
            'cod_payment' => array(
                'name'  => __( '新增取代貨到付款方式', 'woomp' ),
                'type'  => 'checkbox',
                'desc'  => __( '開啟此選項以取代貨到付款方式', 'woomp' ),
                'id'    => 'wc_woomp_setting_cod_payment',
                'class' => 'toggle',
                'desc_tip' => true,
                'default'  => 'yes',
                'std' => 'yes'
            ),
            'product_variations_ui' => array(
                'name'  => __( '變化商品編輯介面', 'woomp' ),
                'type'  => 'checkbox',
                'desc'  => __( '開啟此選項以套用好用版變化商品操作介面', 'woomp' ),
                'id'    => 'wc_woomp_setting_product_variations_ui',
                'class' => 'toggle',
                'desc_tip' => true,
                'default'  => 'yes',
                'std' => 'yes'
            ),
            'place_order_text' => array(
                'name'  => __( '結帳按鈕文字設定', 'woomp' ),
                'type'  => 'text',
                'desc'  => __( '設定結帳頁確定購買按鈕的文字內容', 'woomp' ),
                'id'    => 'wc_woomp_setting_place_order_text',
                'class' => 'toggle',
                'desc_tip' => true,
                'default'  => '',
            ),
            'section_end' => array(
                'type' => 'sectionend',
                'id' => 'wc_woomp_setting_section_end'
            )
        );

        return apply_filters( 'wc_woomp_setting_settings', $settings );
    }

    public static function set_checkbox_toggle(){
        global $pagenow;
        if( 'admin.php' === $pagenow ){ ?>
        <style>
            input.toggle[type=checkbox]{
                height: 0;
                width: 0;
                visibility: hidden;
            }

            input.toggle + label {
                cursor: pointer;
                text-indent: -9999px;
                width: 50px;
                height: 26px;
                background: grey;
                display: block;
                border-radius: 100px;
                position: relative;
            }

            input.toggle + label:after {
                content: '';
                position: absolute;
                top: 3px;
                left: 3px;
                width: 20px;
                height: 20px;
                background: #fff;
                border-radius: 40px;
                transition: 0.3s;
            }

            input.toggle:checked + label {
                background: #cc99c2;
            }

            input.toggle:checked + label:after {
                left: calc(100% - 3px);
                transform: translateX(-100%);
            }

            input.toggle + label:active:after {
                width: 130px;
            }

            .form-table td fieldset label[for=wc_woomp_setting_replace],
            .form-table td fieldset label[for=wc_woomp_setting_billing_country_pos],
            .form-table td fieldset label[for=wc_woomp_setting_tw_address],
            .form-table td fieldset label[for=wc_woomp_setting_one_line_address],
            .form-table td fieldset label[for=wc_woomp_setting_cod_payment],
            .form-table td fieldset label[for=wc_woomp_setting_product_variations_ui] {
                margin-top: 0!important;
                margin-left: -10px!important;
                margin-bottom: 3px!important;
            }

            legend + label[for=wc_woomp_setting_replace]:after,
            legend + label[for=wc_woomp_setting_billing_country_pos]:after,
            legend + label[for=wc_woomp_setting_tw_address]:after,
            legend + label[for=wc_woomp_setting_one_line_address]:after,
            legend + label[for=wc_woomp_setting_cod_payment]:after,
            legend + label[for=wc_woomp_setting_product_variations_ui]:after {
                content: '停用 / 啟用';
                margin-left: 10px;
            }
        </style>
        <script>
            var $ = jQuery.noConflict();
            $(document).ready(function(){
                $('#wc_woomp_setting_replace').after('<label for="wc_woomp_setting_replace">Toggle</label>')
                $('#wc_woomp_setting_billing_country_pos').after('<label for="wc_woomp_setting_billing_country_pos">Toggle</label>')
                $('#wc_woomp_setting_tw_address').after('<label for="wc_woomp_setting_tw_address">Toggle</label>')
                $('#wc_woomp_setting_one_line_address').after('<label for="wc_woomp_setting_one_line_address">Toggle</label>')
                $('#wc_woomp_setting_cod_payment').after('<label for="wc_woomp_setting_cod_payment">Toggle</label>')
                $('#wc_woomp_setting_product_variations_ui').after('<label for="wc_woomp_setting_product_variations_ui">Toggle</label>')
            })
        </script>
        <?php
        }
    }

}

Woomp_Setting::init();