<?php

declare( strict_types = 1 );

namespace J7\Payuni\Infrastructure\Http;

use J7\Payuni\Shared\Enums\EMode;
use PAYUNI\APIs\Payment;

/**
 * 到 payuni 請求類
 *
 * @see https://docs.payuni.com.tw/web/#/29/383
 */
final class HttpClient {
    
    private const TIMEOUT = 60;
    private const USER_AGENT = 'payuni';
    private const VERSION = '3.0';
    private const IS_PLATFORM = 1;
    
    private EMode $mode;
    
    
    public function __construct() {
        $this->set_properties();
        
    }
    
    public function post( string $endpoint, array $request_body = [] ): array {
        try {
            $options = [
                'body'       => \wp_json_encode( $request_body ),
                'blocking'   => true,
                'timeout'    => self::TIMEOUT,
                'user-agent' => self::USER_AGENT,
            ];
            
            $api_url = $this->mode->base_api_url() . $endpoint;
            
            $response = \wp_remote_post( $api_url, $options );
            if( \is_wp_error( $response ) ) {
                throw new \Exception( $response->get_error_message() );
            }
            /** @var array<string, mixed>|array{code: int, msg: string} $response_body */
            $response_body = \json_decode( \wp_remote_retrieve_body( $response ), true );
            
            \do_action( 'woomp_payuni_log', 'info', "{$endpoint} API 發送結果", [
                'endpoint' => $endpoint,
                'body'     => $request_body,
                'result'   => $response_body
            ] );
            
            return $response_body;
        }
        catch ( \Throwable $e ) {
            \do_action( 'woomp_payuni_log', 'error', $e->getMessage(), [] );
            throw $e;
        }
    }
    
    public function get_sdk_token():array {
        $encrypt_info = [
            'MerID'        => $this->mode->merchant_id(),
            'Timestamp'    => \time(),
            'IFrameDomain' => \untrailingslashit( \site_url() )
        ];
        return $this->post( '/iframe/token_get', $this->get_auth_body_params( $encrypt_info ) );
    }
    
    /** 類別初始化時設置屬性 */
    private function set_properties(): void {
        $is_test_mode = \wc_string_to_bool( \get_option( 'payuni_payment_testmode' );
        $this->mode = $is_test_mode ? EMode::TEST : EMode::PROD;
        $this->api_url = $this->mode->base_api_url();
    }
    
    
    private function get_auth_body_params( array $encrypt_info = [] ): array {
        $parameter = [];
        $parameter['MerID'] = $this->mode->merchant_id();
        $parameter['Version'] = self::VERSION;
        $parameter['EncryptInfo'] = Payment::encrypt( $encrypt_info );
        $parameter['HashInfo'] = Payment::hash_info( $parameter['EncryptInfo'] );
        $parameter['IsPlatForm'] = self::IS_PLATFORM;
        return $parameter;
        
    }
}