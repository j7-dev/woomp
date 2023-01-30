<?php

namespace InvoicePorter;

use InvoicePorter\Contracts\ApiRequest;
use GuzzleHttp;

class EzpayApi implements ApiRequest
{
    private $instance;

    /**
     * HTTP client
     *
     * @var object
     */
    private $httpClient;

    public function __construct($instance)
    {
        $this->instance = $instance;

        $apiGate = $this->instance->isProduction
                   ? $this->instance->config['api-gate']
                   : $this->instance->config['api-gate-testing'];

        $this->httpClient = new GuzzleHttp\Client([
            'base_uri' => $apiGate,
        ]);
    }

    public function send(string $apiUri, array $postData, $doCheckCode = true, $invoiceData = null)
    {
        $response = $this->httpClient->request('post', $apiUri, [
            'form_params' => [
                'MerchantID_' => $this->instance->merchantID,
                'PostData_' => $this->encryptPostData($postData),
            ],
        ]);

        if ($response->getStatusCode() != 200) {
            throw new \Exception('串接回應錯誤');
        }
        
        $response = json_decode($response->getBody()->getContents());

        $response = (object) [
            'status' => $response->Status,
            'message' => $response->Message,
            'result' => is_array($response->Result) && ! count($response->Result)
                        ? null
                        : json_decode($response->Result), // 原回應只為 string，需再次 json_decode
        ];

        if ($response->status == 'SUCCESS' && $doCheckCode) {
            $this->validateCheckCode($response->result, $invoiceData);
        };

        return $response;
    }

    /**
     * 加密所需
     * 
     * @return string
     */
    public function encryptPostData(array $postData)
    {
        $postDataStr = http_build_query($postData); // 轉成字串排列
        
        if (phpversion() > 7) {
            // php 7 以上版本加密
            $postData = trim(bin2hex(openssl_encrypt($this->strAddPadding($postDataStr),
                'AES-256-CBC', $this->instance->hashKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $this->instance->hashIV)));
        } else {
            // php 7 之前版本加密
            $postData = trim(bin2hex(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->instance->hashKey,
               $this->instance->strAddPadding($postDataStr), MCRYPT_MODE_CBC, $this->instance->hashIV)));
        }

        return $postData;
    }

    /**
     * 加密所需
     * 
     * @return string
     */
    private function strAddPadding($string, $blocksize = 32)
    {
        $len = strlen($string);
        $pad = $blocksize - ($len % $blocksize);
        $string .= str_repeat(chr($pad), $pad);
    
        return $string;
    }

    private function validateCheckCode($result, $invoiceData)
    {
        $responseChcekCode = $result->CheckCode;
        
        $result = $invoiceData ? $invoiceData : $result; // 若經由查詢發票帶入，使用查詢發票的資料

        $checkCode = [
            'MerchantID' => $this->instance->merchantID,
            'MerchantOrderNo' => $result->MerchantOrderNo,
            'InvoiceTransNo' => $result->InvoiceTransNo,
            'TotalAmt' => $result->TotalAmt,
            'RandomNum' => $result->RandomNum,
        ];
            
        ksort($checkCode);
        $checkStr = http_build_query($checkCode);
        $checkCode = strtoupper(hash(
            'sha256',
            "HashIV={$this->instance->hashIV}&" . $checkStr . "&HashKey={$this->instance->hashKey}"
        ));
            
        if ($checkCode !== $responseChcekCode) {
            throw new \Exception('check code 檢查錯誤');
        }
    }
}