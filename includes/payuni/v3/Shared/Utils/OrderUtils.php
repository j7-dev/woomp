<?php

declare( strict_types = 1 );

namespace J7\Payuni\Shared\Utils;

final class OrderUtils {
    
    
    /**
     * 更新暫存資料
     *
     * @param \WC_Order $order
     * @param array     $posted_data
     *
     * @return void
     */
    public static function update_tmp_data( \WC_Order $order, array $posted_data ): void {
        $card_hash_tmp = $posted_data['card_hash_tmp'] ?? '';
        $sdk_token_tmp = $posted_data['sdk_token_tmp'] ?? '';
        if( !$card_hash_tmp && !$sdk_token_tmp ) {
            return;
        }
        $order->update_meta_data( 'card_hash_tmp', $card_hash_tmp );
        $order->update_meta_data( 'sdk_token_tmp', $sdk_token_tmp );
        $order->save_meta_data();
    }
    
    /**
     * 取得暫存資料
     *
     * @param \WC_Order $order
     *
     * @return array{0:string, 1:string} [sdk_token, card_hash]
     */
    public static function get_tmp_data( \WC_Order $order ): array {
        $card_hash_tmp = $order->get_meta( 'card_hash_tmp', true );
        $sdk_token_tmp = $order->get_meta( 'sdk_token_tmp', true );
        return [ $sdk_token_tmp, $card_hash_tmp ];
    }
    
    
    /**
     * 刪除暫存資料
     *
     * @param \WC_Order $order
     *
     * @return void
     */
    public static function delete_tmp_data( \WC_Order $order ): void {
        $order->delete_meta_data( 'card_hash_tmp' );
        $order->delete_meta_data( 'sdk_token_tmp' );
        $order->save_meta_data();
    }
    
    /** 擴展暫存資料欄位 */
    public static function extend_checkout_field( array $fields ): array {
        $fields['billing']['card_hash_tmp'] = [
            'type' => 'hidden', // 欄位類型設定為 hidden
        ];
        $fields['billing']['sdk_token_tmp'] = [
            'type' => 'hidden', // 欄位類型設定為 hidden
        ];
        return $fields;
    }
    
}