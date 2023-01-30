<?php

namespace InvoicePorter;

use InvoicePorter\AbstractInvoice;
use InvoicePorter\EzpayApi;

class EzpayInvoice extends AbstractInvoice
{
    /**
     * 商店代號
     *
     * @var string
     */
    public $merchantID;

    /**
     * hashKey
     *
     * @var string
     */
    public $hashKey;

    /**
     * hashIV
     *
     * @var string
     */
    public $hashIV;

    /**
     * Class constructor
     * 
     * @param array $account
     * @param bool $isProduction
     */
    public function __construct(array $account, $isProduction = true)
    {
        parent::__construct($isProduction);

        $this->config = (require_once dirname(dirname(__FILE__)) . '/config/config.php')['ezpay'];

        $this->merchantID = $account['merchantID'];
        $this->hashKey = $account['hashKey'];
        $this->hashIV = $account['hashIV'];
    }

    public function create($postData)
    {
        $api = [
            'uri' => 'invoice_issue',
            'version' => '1.4',
        ];

        self::sendRequest($postData, $api);

        return $this;
    }

    public function info($postData)
    {
        $api = [
            'uri' => 'invoice_search',
            'version' => '1.2',
        ];

        // 判斷是否為轉址至平台
        if (isset($postData['DisplayFlag']) && $postData['DisplayFlag'] == 1) {
            self::infoRedirect($postData, $api);
            die;
        }

        self::sendRequest($postData, $api);

        return $this;
    }

    public function invalid($postData)
    {
        /**
         * 測試用資料
         * MerchantID: 32365158
         * MerchantOrderNo: 1589331622
         * InvoiceNumber: AA00000076
         * TotalAmt: 500
         * InvoiceTransNo: 20051309002377869
         * RandomNum: 0991
         */

        /**
         * 作廢發票預設不檢查 checkcode，因官方回應缺少屬性，
         * 需預先做次發票查詢，取回需要的參數，
         * 若 $postData 中有「發票隨機碼 RandomNum」時，則進行 checkcode 檢查
         */
        
        $api = [
            'uri' => 'invoice_invalid',
            'version' => '1.0',
        ];

        $doCheckCode = false;
        $invoiceData = null;

        if (array_key_exists('RandomNum', $postData)) {
            // 先查詢發票資訊，用於帶入檢查 checkcode 時的參數
            $invoiceData = $this->info([
                // 使用發票號碼及隨機碼查詢
                'SearchType' => 0,
                'InvoiceNumber' => $postData['InvoiceNumber'],
                'RandomNum' => $postData['RandomNum'],
            ])->getResult();
        }

        if ($invoiceData) {
            $doCheckCode = true;
        }
        
        self::sendRequest($postData, $api, $doCheckCode, $invoiceData);

        return $this;
    }

    protected function sendRequest($postData, $api, $doCheckCode = true, $invoiceData = null)
    {
        $postData = $this->mergeCommonPostData($postData, $api['version']);

        $response = (new EzpayApi($this))->send($api['uri'], $postData, $doCheckCode, $invoiceData);

        self::handleResponse($response);
    }

    protected function mergeCommonPostData($postData, $apiVersion)
    {
        $default = [
            'Version' => $apiVersion, // 每支 API 不同
            'RespondType' => $this->config['response-type'],
            'TimeStamp' => time(), // 請以 time() 格式
        ];

        return array_merge($default, $postData);
    }

    protected function handleResponse($response)
    {
        $rawResponse = (object) [
            'raw' => $response,
            'message' => $response->message,
            'code' => $response->status,
            'result' => $response->result,
        ];

        parent::setResponse($rawResponse, $code = $response->status);
    }

    protected function infoRedirect($postData, $api)
    {
        $postData = $this->mergeCommonPostData($postData, $api['version']);
        $postData = (new EzpayApi($this))->encryptPostData($postData);

        $apiGate = $this->isProduction
                   ? $this->config['api-gate']
                   : $this->config['api-gate-testing'];
        
        $url = $apiGate . $api['uri'];

echo <<<EOT
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
    </head>
<body>
    <form id="form" method="post" action="$url">
        <input type="hidden" name="MerchantID_" value="$this->merchantID">
        <input type="hidden" name="PostData_" value="$postData">
    </form>
    <script>document.getElementById('form').submit();</script>
</body>
</html>
EOT;
    }
}