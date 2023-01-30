<?php

namespace InvoicePorter;

abstract class AbstractInvoice
{
    /**
     * 套件設定檔
     *
     * @var array
     */
    public $config;

    /**
     * 運行環境
     *
     * @var boolean
     */
    public $isProduction;

    /**
     * API response
     *
     * @var object|null
     */
    public $response = null;

    /**
     * Response data is ready
     *
     * @var object|null
     */
    private $responseIsReady = false;

    /**
     * Class constructor.
     */
    public function __construct($isProduction)
    {
        self::exceptionHandler();
        self::setProduction($isProduction);
    }
    
    protected function setProduction(bool $isProduction = true)
    {
        return $this->isProduction = $isProduction;
    }

    abstract protected function sendRequest($postData, $api, $doCheckCode = true, $invoiceData = null);
    
    protected function setResponse($response, $code)
    {
        $this->response = (object) [
            'success' => $this->isOK($code),
            'message' => $response->message,
            'code' => $response->code,
            'result' => $response->result,
            'raw' => $response->raw,
        ];

        $this->responseIsReady = true;
    }

    /**
     * return $this
     */
    public function getResponse()
    {
        $this->checkHasResponse();

        return $this->response;
    }

    /**
     * return $this
     */
    public function getResult($property = null)
    {
        $this->checkHasResponse();
        
        $result = $this->response->result;

        return $property
               ? $result->{$property}
               : $result;
    }

    /**
     * 是否成功
     * return boolean
     */
    public function isOK($code = null)
    {
        if ($code) {
            return $code === $this->config['success-code'];
        }
        
        $this->checkHasResponse();

        if (isset($this->response->success) && is_bool($this->response->success)) {
            return $this->response->success;
        }

        throw new \Exception('不明錯誤');
    }

    public function getErrorMessage()
    {
        $this->checkHasResponse();

        return $this->isOK()
               ? null
               : [
                    'message' => $this->response->message,
                    'code' => $this->response->code
                 ];
    }

    protected function checkHasResponse()
    {
        if ($this->response === null) {
            throw new \Exception('未進行發票動作，請先進行開立開票或查詢等動作');
        }

        if (! $this->responseIsReady) {
            throw new \Exception('未進行 API 回應的資料設置');
        }

        return true;
    }

    /**
     * Exception handler
     *
     * @return string
     */
    protected function exceptionHandler()
    {
        set_exception_handler(function($exception) {
            http_response_code(500);
            echo "Error: {$exception->getMessage()},"
               . "File: {$exception->getFile()} Line: {$exception->getLine()}";
        });
    }
}
