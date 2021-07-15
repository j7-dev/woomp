<?php
final class RY_WEI_update
{
    public static function update()
    {
        $now_version = RY_WEI::get_option('version');

        if ($now_version === false) {
            $now_version = '0.0.0';
        }
        if ($now_version == RY_WEI_VERSION) {
            return;
        }

        if (version_compare($now_version, '1.0.5', '<')) {
            RY_WEI::update_option('version', '1.0.5');
        }

        if (version_compare($now_version, '1.1.0', '<')) {
            if ('yes' == RY_WEI::get_option('enabled_invoice', 'no')) {
                if (!is_callable('openssl_encrypt') || !is_callable('openssl_decrypt')) {
                    add_action('admin_notices', function () {
                        echo '<div class="error"><p>' . __('ECPay invoice method failed to enable!', 'ry-woocommerce-ecpay-invoice')
                        . __('Required PHP function openssl_encrypt and openssl_decrypt.', 'ry-woocommerce-ecpay-invoice')
                        . '</p></div>';
                    });
                    RY_WEI::update_option('enabled_invoice', 'no');
                }
            }

            RY_WEI::update_option('version', '1.1.0');
        }

        if (version_compare($now_version, '1.1.13', '<')) {
            RY_WEI::update_option('version', '1.1.13');
        }
    }
}
