<?php
abstract class RY_ECPay_Invoice
{
    protected static $encrypt_method = 'aes-128-cbc';

    protected static function generate_trade_no($order_id, $order_prefix = '')
    {
        $trade_no = $order_prefix . $order_id . 'TS' . rand(0, 9) . strrev((string) time());
        $trade_no = substr($trade_no, 0, 20);
        $trade_no = apply_filters('ry_ecpay_trade_no', $trade_no);
        return substr($trade_no, 0, 20);
    }

    protected static function build_args($data, $MerchantID)
    {
        $args = [
            'MerchantID' => $MerchantID,
            'RqHeader' => [
                'Timestamp' => new DateTime('', new DateTimeZone('Asia/Taipei')),
                'RqID' => wc_rand_hash(),
                'Revision' => '3.0.0'
            ],
            'Data' => wp_json_encode($data)
        ];
        $args['RqHeader']['Timestamp'] = $args['RqHeader']['Timestamp']->format('U');

        return $args;
    }

    protected static function urlencode($string)
    {
        $string = str_replace(
            ['%2D', '%2d', '%5F', '%5f', '%2E', '%2e', '%2A', '%2a', '%21', '%28', '%29'],
            [  '-',   '-',   '_',   '_',    '.',  '.',   '*',   '*',   '!',   '(',   ')'],
            urlencode($string)
        );
        return $string;
    }

    protected static function link_server($post_url, $args, $HashKey, $HashIV)
    {
        wc_set_time_limit(40);

        $args['Data'] = self::urlencode($args['Data']);
        $args['Data'] = openssl_encrypt($args['Data'], self::$encrypt_method, $HashKey, 0, $HashIV);

        $response = wp_remote_post($post_url, [
            'timeout' => 20,
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => wp_json_encode($args)
        ]);

        if (is_wp_error($response)) {
            RY_WEI_Invoice::log('Link ECPay failed. Post error: ' . implode("\n", $response->get_error_messages()), 'error');
            return '';
        }

        if ($response['response']['code'] != '200') {
            RY_WEI_Invoice::log('Link ECPay failed. Http code: ' . $response['response']['code'], 'error');
            return '';
        }

        RY_WEI_Invoice::log('Link ECPay result: ' . $response['body']);
        $result = @json_decode($response['body']);

        if (!is_object($result)) {
            RY_WEI_Invoice::log('Link ECPay failed. Response parse failed.', 'error');
            return '';
        }

        if (!(isset($result->TransCode) && $result->TransCode == 1)) {
            RY_WEI_Invoice::log('Link ECPay failed. Result Error: ' . $result->TransMsg, 'error');
            return '';
        }

        $result->Data = openssl_decrypt($result->Data, self::$encrypt_method, $HashKey, 0, $HashIV);
        $result->Data = urldecode($result->Data);
        $result->Data = @json_decode($result->Data);

        if (!is_object($result->Data)) {
            RY_WEI_Invoice::log('Link ECPay failed. Data decrypt failed.', 'error');
            return '';
        }

        RY_WEI_Invoice::log('Link ECPay result: ' . var_export($result->Data, true));

        return $result->Data;
    }

    protected static function generate_check_value($args, $HashKey, $HashIV, $hash_algo, $skip_args = [])
    {
        $skip_args[] = 'CheckMacValue';
        foreach ($skip_args as $key) {
            unset($args[$key]);
        }

        ksort($args, SORT_STRING | SORT_FLAG_CASE);

        $args_string = [];
        $args_string[] = 'HashKey=' . $HashKey;
        foreach ($args as $key => $value) {
            $args_string[] = $key . '=' . $value;
        }
        $args_string[] = 'HashIV=' . $HashIV;

        $args_string = implode('&', $args_string);
        $args_string = self::urlencode($args_string);
        $args_string = strtolower($args_string);
        $check_value = hash($hash_algo, $args_string);
        $check_value = strtoupper($check_value);

        return $check_value;
    }

    protected static function add_check_value($args, $HashKey, $HashIV, $hash_algo, $skip_args = [])
    {
        $args['CheckMacValue'] = self::generate_check_value($args, $HashKey, $HashIV, $hash_algo, $skip_args);
        return $args;
    }

    protected static function old_link_server($post_url, $args, $HashKey, $HashIV)
    {
        wc_set_time_limit(40);

        $send_body = [];
        foreach ($args as $key => $value) {
            $send_body[] = $key . '=' . $value;
        }

        $response = wp_remote_post($post_url, [
            'timeout' => 20,
            'body' => implode('&', $send_body)
        ]);

        if (is_wp_error($response)) {
            RY_WEI_Invoice::log('Link ECPay failed. Post error: ' . implode("\n", $response->get_error_messages()), 'error');
            return '';
        }

        if ($response['response']['code'] != '200') {
            RY_WEI_Invoice::log('Link ECPay failed. Http code: ' . $response['response']['code'], 'error');
            return '';
        }

        RY_WEI_Invoice::log('Link ECPay result: ' . $response['body']);

        parse_str($response['body'], $result);

        if (!is_array($result)) {
            RY_WEI_Invoice::log('Link ECPay failed. Response parse failed.', 'error');
            return '';
        }

        if (!isset($result['CheckMacValue'])) {
            RY_WEI_Invoice::log('Link ECPay failed. Response parse failed.', 'error');
            return '';
        }

        $tmp_check_value = self::generate_check_value($result, $HashKey, $HashIV, 'md5');
        if ($result['CheckMacValue'] != $tmp_check_value) {
            RY_WEI_Invoice::log('Invalid failed. Response check failed. Response:' . $result['CheckMacValue'] . ' Self:' . $tmp_check_value, 'error');
            return '';
        }

        return $result;
    }

    protected static function get_order_id($ipn_info, $order_prefix = '')
    {
        if (isset($ipn_info['od_sob'])) {
            $order_id = $ipn_info['od_sob'];
            $order_id = substr($order_id, strlen($order_prefix), strrpos($order_id, 'TS'));
            $order_id = (int) $order_id;
            if ($order_id > 0) {
                return $order_id;
            }
        }
        return false;
    }

    protected static function die_success()
    {
        die('1|OK');
    }

    protected static function die_error()
    {
        die('0|');
    }
}
