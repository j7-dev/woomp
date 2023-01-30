
<?php

require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';

use InvoicePorter\EzpayInvoice;

$account = (require '../config/ezpay-invoice.php')['testing'];
$invoice = new EzpayInvoice($account, $isProduction = false);

// 先開立再作廢
$invoice->create([
    'Status' => '1', // 1=立即開立，0=待開立，3=延遲開立
    'CreateStatusTime' => null, // Status = 3 時設置
    'MerchantOrderNo' => time(),
    'BuyerName' => '王大品',
    'BuyerUBN' => '54352706',
    'BuyerAddress' => '台北市南港區南港路二段 97 號 8 樓',
    'BuyerEmail' => '54352706@pay2go.com',
    'Category' => 'B2B', // 二聯 B2C，三聯 B2B
    'TaxType' => '1',
    'TaxRate' => '5',
    'Amt' => '490',
    'TaxAmt' => '10',
    'TotalAmt' => '500',
    'PrintFlag' => 'Y',
    'ItemName' => '商品一|商品二', // 多項商品時，以「|」分開
    'ItemCount' => '1|2', // 多項商品時，以「|」分開
    'ItemUnit' => '個|個', // 多項商品時，以「|」分開
    'ItemPrice' => '476|100', // 多項商品時，以「|」分開，未稅價格
    'ItemAmt' => '476|200', // 多項商品時，以「|」分開，未稅價格
    'Comment' => '備註',
]);

// 作廢
$invoice->invalid([
    'InvoiceNumber' => $invoice->getResult('InvoiceNumber'), // 發票號碼
    'RandomNum' => $invoice->getResult('RandomNum'), // (選擇性) 若需檢查 checkcode，需帶入
    'InvalidReason' => '訂單取消', // 作廢原因
]);

dd(
    $invoice->isOK(),
    $invoice->getResponse(),
    $invoice->getResult(),
    $invoice->getErrorMessage()
);