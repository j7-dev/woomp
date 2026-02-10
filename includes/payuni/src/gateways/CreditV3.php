<?php
/**
 * Payuni_Payment_Credit class file
 *
 * @package payuni
 */

namespace PAYUNI\Gateways;

use J7\Payuni\Contracts\DTOs\TradeReqDTO;

\defined( 'ABSPATH' ) || exit;

/**
 * Payuni_Payment_Credit class for Credit Card payment
 */
class CreditV3 extends AbstractGateway {
    
    public const ID = 'payuni-credit-v3';
    
    /** Constructor */
    public function __construct() {
        $this->id = self::ID;
        parent::__construct();
        $this->has_fields = true;
        // $this->order_button_text = __( '統一金流 PAYUNi 信用卡', 'woomp' );
        
        $this->method_title = '統一金流 PAYUNi 信用卡 v3';
        $this->method_description = '透過統一金流 PAYUNi 信用卡進行站內付款';
        
        $this->init_form_fields();
        $this->init_settings();
        
        $this->title = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );
        $this->supports = [ 'products', 'refunds', 'tokenization' ];
        $this->api_endpoint_url = 'api/credit';
        
        \add_action( "woocommerce_update_options_payment_gateways_{$this->id}", [ $this, 'process_admin_options', ] );

//        \add_action( 'woocommerce_before_checkout_form', [ $this, 'form' ] );
    }
    
    /** @return void 設定後台 form fields */
    public function init_form_fields(): void {
        $this->form_fields = [
            'enabled'     => [
                'title'   => __( 'Enable/Disable', 'woocommerce' ),
                'type'    => 'checkbox',
                'label'   => \sprintf( __( 'Enable %s', 'woomp' ), $this->method_title ),
                'default' => 'no',
            ],
            'title'       => [
                'title'       => __( 'Title', 'woocommerce' ),
                'type'        => 'text',
                'default'     => $this->method_title,
                'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => __( 'Description', 'woocommerce' ),
                'type'        => 'textarea',
                'css'         => 'width: 400px;',
                'default'     => $this->order_button_text,
                'description' => __(
                    'This controls the description which the user sees during checkout.', 'woocommerce'
                ),
                'desc_tip'    => true,
            ],
        ];
    }
    
    public function validate_fields(): bool {
        return true;
    }
    
    /**
     * 輸出付款表單欄位
     *
     * @description 在結帳頁面輸出信用卡輸入框的容器，供 PayUni SDK iframe 渲染
     * @return void
     */
    public function payment_fields(): void {
        // 輸出付款方式描述
        if( $this->description ) {
            echo \wpautop( \wptexturize( $this->description ) );
        }
        
        // 輸出信用卡輸入框容器
        $html = <<<HTML
            <div class="payuni-credit-v3-form">
                <div class="payuni-form-group">
                    <label for="put_card_no">信用卡號碼</label>
                    <div id="put_card_no"></div>
                </div>
                <div class="payuni-form-group">
                    <label for="put_card_exp">有效期限</label>
                    <div id="put_card_exp"></div>
                </div>
                <div class="payuni-form-group">
                    <label for="put_card_cvc">安全碼</label>
                    <div id="put_card_cvc"></div>
                </div>
            </div>
        HTML;
        
        echo $html;
    }
    
    
    /**
     * 處理付款
     *
     * 共用邏輯：
     * 1. order note 紀錄 gateway
     * 2. 支付順利的話就扣庫存，不順利就 throw Exception
     *
     * @param int $order_id 訂單 ID
     *
     * @return array{result:string, redirect?:string} 'success'|'failure'
     */
    public function process_payment( $order_id ): array {
        $order = \wc_get_order( $order_id );
        /** @var \WC_Order $order */
        $default = [
            'result'   => 'success',
            'redirect' => $order->get_checkout_order_received_url(),
            'order_id' => $order_id,
        ];
        $params = TradeReqDTO::of( $order )->to_array();
        
        return \array_merge( $default, $params );
    }
    
    /**
     * Display payment detail after order table
     *
     * @param \WC_Order $order The order object.
     *
     * @return void
     */
    public function get_detail_after_order_table( \WC_Order $order ) {
        if( $order->get_payment_method() !== $this->id ) {
            return;
        }
        
        
        $status = \esc_html( $order->get_meta( '_payuni_resp_status', true ) );
        $message = \esc_html( $order->get_meta( '_payuni_resp_message', true ) );
        $trade_no = \esc_html( $order->get_meta( '_payuni_resp_trade_no', true ) );
        $card_4no = \esc_html( $order->get_meta( '_payuni_card_number', true ) );
        
        $html = <<<HTML
            <h2 class="woocommerce-order-details__title">交易明細</h2>
            <div class="responsive-table">
                <table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
                    <tbody>
                        <tr>
                            <th>狀態碼：</th>
                            <td>{$status}</td>
                        </tr>
                        <tr>
                            <th>交易訊息：</th>
                            <td>{$message}</td>
                        </tr>
                        <tr>
                            <th>交易編號：</th>
                            <td>{$trade_no}</td>
                        </tr>
                        <tr>
                            <th>卡號末四碼：</th>
                            <td>{$card_4no}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        HTML;
        
        echo $html;
    }
    
    
    /**
     * Checkout fields 結帳欄位
     * Payment form on checkout page copy from WC_Payment_Gateway_CC
     * To add the input name and get value with $_POST
     *
     * @return void
     */
    public function form(): void {}
}
