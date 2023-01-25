<?php
/**
 * Created by PhpStorm.
 * User: Jerry
 * Date: 2017/11/13
 * Time: 上午10:05
 */

defined('ABSPATH') || exit;

/**
 * Settings for PayPal Gateway.
 */


return array(
    'enabled' => array(
        'title' => __('Enable/Disable', 'woocommerce'),
        'type' => 'checkbox',
        'label' => __('Enable', 'woocommerce'),
        'default' => 'no'
    ),
    'title' => array(
        'title' => __('Title', 'woocommerce'),
        'type' => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
        'default' => __('PChomePay', 'woocommerce'),
        'desc_tip' => true,
    ),
    'description' => array(
        'title' => __('Description', 'woocommerce'),
        'type' => 'textarea',
        'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
        'default' => __('透過 PChomePay支付連 付款，會連結到 PChomePay支付連 付款頁面。', 'woocommerce'),
    ),
//    'test_mode' => array(
//        'title' => __('測試模式', 'woocommerce'),
//        'label' => __('Enable', 'woocommerce'),
//        'type' => 'checkbox',
//        'description' => __('使用支付連 SandBox 測試環境。', 'woocommerce'),
//        'default' => 'no'
//    ),
//    'app_id' => array(
//        'title' => __('APP ID', 'woocommerce'),
//        'type' => 'text',
//        'default' => ''
//    ),
//    'secret' => array(
//        'title' => __('SECRET', 'woocommerce'),
//        'type' => 'text',
//        'description' => __("供正式環境使用之Secret。", 'woocommerce'),
//        'default' => ''
//    ),
//    'sandbox_secret' => array(
//        'title' => __('SECRET for test mode', 'woocommerce'),
//        'type' => 'text',
//        'description' => __("供測試環境使用之Secret。", 'woocommerce'),
//        'default' => ''
//    ),
//    'debug' => array(
//        'title'       => __( 'Debug log', 'woocommerce' ),
//        'type'        => 'checkbox',
//        'label'       => __( 'Enable logging', 'woocommerce' ),
//        'default'     => '',
//        'description' => sprintf( __( '記錄 PChomePay 事件，位於 %s', 'woocommerce' ), '<code>' . WC_Log_Handler_File::get_log_file_path( 'pchomepay' ) . '</code>' ),
//    ),
//    'payment_methods' => array(
//        'title' => __('付款方式', 'woocommerce'),
//        'type' => 'multiselect',
//        'description' => __('按下 CTRL 與 滑鼠右鍵 以選擇多種付款方式<br><br>7-11超商取貨不適用金額低於65元之訂單。', 'woocommerce'),
//        'options' => array(
//            'CARD' => __('信用卡'),
//            'ATM' => __('ATM'),
//            'EACH' => __('銀行支付'),
//            'ACCT' => __('支付連餘額支付'),
//            'IPL7' => __('7-11超商取貨')
//        )
//    ),
//    'card_installment' => array(
//        'title' => __('信用卡分期', 'woocommerce'),
//        'type' => 'multiselect',
//        'description' => __('按下 CTRL 與 滑鼠右鍵 以選擇多種付款方式<br><br>信用卡分期不適用於金額低於30元之訂單。', 'woocommerce'),
//        'options' => array(
//            'CRD_0' => __('一次付清', 'woocommerce'),
//            'CRD_3' => __('3 期', 'woocommerce'),
//            'CRD_6' => __('6 期', 'woocommerce'),
//            'CRD_12' => __('12 期', 'woocommerce'),
//        )
//    ),
//    'atm_expiredate' => array(
//        'title' => __('ATM 虛擬帳號繳費期限', 'woocommerce'),
//        'type' => 'text',
//        'description' => __("請輸入 ATM 虛擬帳號繳費期限 (1~5 天)，預設 5 天。", 'woocommerce'),
//        'default' => 5
//    ),
//    'card_last_number' => array(
//        'title' => __('記錄信用卡末四碼', 'woocommerce'),
//        'label' => __('Enable', 'woocommerce'),
//        'type' => 'checkbox',
//        'description' => __('紀錄買家信用卡末四碼資訊於訂單備註。', 'woocommerce'),
//        'default' => 'yes'
//    )
//,
//    'customize_order_received_text' => array(
//        'title' => __('訂單成立後顯示訊息', 'woocommerce'),
//        'type' => 'textarea',
//        'description' => __('訂單成立顯示訊息。', 'woocommerce'),
//        'default' => __('', 'woocommerce')
//    )
);