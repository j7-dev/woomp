<?php

declare( strict_types = 1 );

namespace J7\Payuni;

use J7\Payuni\Contracts\DTOs\SdkDTO;
use J7\Payuni\Contracts\DTOs\SettingDTO;
use J7\Payuni\Infrastructure\Http\HttpClient;

final class Bootstrap {
    
    private const LOG_SOURCE = 'payuni_payment_v3';
    
    /** Register hooks */
    public static function register_hooks(): void {
        
        // TEST ----- ▼ 測試特定 hook 記得刪除 ----- //
        \add_action( 'init', function() {
            if( isset( $_GET['test'] ) ) {
                $token = ( new HttpClient() )->get_sdk_token();
                $sdk = SdkDTO::from( $token );
                
                echo '<pre>';
                \var_dump( $sdk );
                echo '</pre>';
            }
            
        } );
        // TEST ---------- END ---------- //
        
        
        \add_action( 'woomp_payuni_log', [ __CLASS__, 'log_handler' ], 10, 3 );
        \add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_checkout_scripts' ] );
//        \add_action( 'send_headers', [ __CLASS__, 'add_csp_header' ] );
    }
    
    /**
     * 紀錄 log
     *
     * @param string $level
     * @param string $message
     * @param array  $args
     *
     * @return void
     */
    public static function log_handler( string $level = 'info', string $message = '', array $args = [] ): void {
        $setting = SettingDTO::instance();
        if( !$setting->enable_log ) {
            return;
        }
        
        $logger = new \WC_Logger();
        
        $context = [
            'source' => self::LOG_SOURCE,
        ];
        
        if( $args ) {
            $context['args'] = $args;
        }
        
        
        $logger->log( $level, $message, $context );
    }
    
    public static function enqueue_checkout_scripts(): void {
        if( !\is_checkout() ) {
            return;
        }
        
        \wp_enqueue_script(
            'uni-payment', 'https://vendor.payuni.com.tw/sdk/uni-payment.js', [], '3.0.0', true
        );
        
        \wp_enqueue_script(
            'uni-payment-checkout', WOOMP_PLUGIN_URL . 'includes/payuni/v3/Applications/assets/js/checkout.js',
            [ 'uni-payment', 'jquery' ], WOOMP_VERSION, true // 放在 footer
        );
    }
    
    public static function add_csp_header(): void {
        \header(
            "Content-Security-Policy: default-src 'self'; script-src 'self' https://vendor.payuni.com.tw https://sandbox-vendor.payuni.com.tw; frame-src 'self' https://vendor.payuni.com.tw https://sandbox-vendor.payuni.com.tw"
        );
    }
}