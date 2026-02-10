<?php

declare( strict_types = 1 );

namespace J7\Payuni\Infrastructure\Http;

use J7\Payuni\Contracts\DTOs\SettingDTO;
use J7\Payuni\Shared\Utils\EncryptUtils;

/**
 * 處理 PayUni 交易請求
 *
 * @description 負責幕後信用卡交易授權的 HTTP 請求處理
 * @see         https://docs.payuni.com.tw/web/#/29/383
 */
final class TradeHandler {
    
    private const TIMEOUT = 60;
    private const USER_AGENT = 'payuni';
    
    /**
     * 執行幕後信用卡交易授權
     *
     * @param array $request_body 交易請求參數
     *
     * @return array 交易回應結果
     * @throws \Exception 當交易失敗時
     */
    public function executeTrade( array $request_body ): array {
        $setting = SettingDTO::instance();
        $api_url = "{$setting->mode->base_api_url()}/iframe/merchant_trade";
        
        try {
            $options = [
                'body'       => $request_body,
                'blocking'   => true,
                'timeout'    => self::TIMEOUT,
                'user-agent' => self::USER_AGENT,
            ];
            
            $response = \wp_remote_post( $api_url, $options );
            
            if( \is_wp_error( $response ) ) {
                throw new \Exception( $response->get_error_message() );
            }
            
            /** @var array $response_body */
            $response_body = \json_decode( \wp_remote_retrieve_body( $response ), true );
            
            \do_action( 'woomp_payuni_log', 'info', '幕後交易授權結果', [
                'endpoint' => $api_url,
                'body'     => $request_body,
                'result'   => $response_body
            ] );
            
            return $response_body;
        }
        catch ( \Throwable $e ) {
            \do_action( 'woomp_payuni_log', 'error', '幕後交易授權失敗: ' . $e->getMessage(), [
                'body' => $request_body
            ] );
            throw $e;
        }
    }
    
    /**
     * 處理交易回調通知
     *
     * @param array $encrypted_data 加密的回調資料
     *
     * @return array 解密後的交易結果
     * @throws \Exception 當解密或驗證失敗時
     */
    public function processNotify( array $encrypted_data ): array {
        $encrypt_info = $encrypted_data['EncryptInfo'] ?? '';
        $hash_info = $encrypted_data['HashInfo'] ?? '';
        
        if( empty( $encrypt_info ) || empty( $hash_info ) ) {
            throw new \Exception( '缺少加密資料' );
        }
        
        // 驗證 Hash
        $calculated_hash = EncryptUtils::hash_info( $encrypt_info );
        if( $calculated_hash !== $hash_info ) {
            throw new \Exception( 'Hash 驗證失敗' );
        }
        
        // 解密交易結果
        $decrypted = EncryptUtils::decrypt( $encrypt_info );
        
        \do_action( 'woomp_payuni_log', 'info', '交易回調通知解密結果', $decrypted );
        
        return $decrypted;
    }
    
    /**
     * 更新訂單狀態
     *
     * @param \WC_Order $order        訂單物件
     * @param array     $trade_result 交易結果
     *
     * @return void
     */
    public function updateOrderStatus( \WC_Order $order, array $trade_result ): void {
        $status = $trade_result['Status'] ?? '';
        $message = $trade_result['Message'] ?? '';
        $trade_no = $trade_result['TradeNo'] ?? '';
        $card_4no = $trade_result['Card4No'] ?? '';
        
        // 儲存交易資訊到訂單
        $order->update_meta_data( '_payuni_resp_status', $status );
        $order->update_meta_data( '_payuni_resp_message', $message );
        $order->update_meta_data( '_payuni_resp_trade_no', $trade_no );
        $order->update_meta_data( '_payuni_card_number', $card_4no );
        
        if( 'SUCCESS' === $status ) {
            // 交易成功
            $order->payment_complete( $trade_no );
            $order->add_order_note(
                \sprintf( '統一金流 PAYUNi 信用卡付款成功。交易編號: %s', $trade_no )
            );
        }
        else {
            // 交易失敗
            $order->update_status(
                'failed', \sprintf( '統一金流 PAYUNi 信用卡付款失敗。狀態: %s, 訊息: %s', $status, $message )
            );
        }
        
        $order->save();
    }
}
