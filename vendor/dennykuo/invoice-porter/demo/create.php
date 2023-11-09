<?php

require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';

use InvoicePorter\EzpayInvoice;

$account = (require '../config/ezpay-invoice.php')['testing'];
$invoice = new EzpayInvoice($account, $isProduction = false);

// $type = 'B2C';
$type = 'B2B';
$totalAmount = 500;
$amountRaw = round($totalAmount / 1.05);
$taxAmount = $totalAmount - $amountRaw;


$invoice->create([
    'Status' => '1', // 1=立即開立，0=待開立，3=延遲開立
    'CreateStatusTime' => null, // Status = 3 時設置
    'MerchantOrderNo' => time(),
    // 'MerchantOrderNo' => '2', // 測試重複開立
    'BuyerName' => '王大品',
    'BuyerUBN' => '54352706',
    // 'BuyerAddress' => '台北市南港區南港路二段 97 號 8 樓',
    'BuyerEmail' => '54352706@pay2go.com',
    'Category' => $type , // 二聯 B2C，三聯 B2B
    'TaxType' => '1',
    'TaxRate' => '5',
    'Amt' => $amountRaw,
    'TaxAmt' => $taxAmount,
    'TotalAmt' => $totalAmount,
    'PrintFlag' => 'Y',
    'ItemName' => '商品一', // 多項商品時，以「|」分開
    'ItemCount' => '1', // 多項商品時，以「|」分開
    'ItemUnit' => '個', // 多項商品時，以「|」分開
    'ItemPrice' => $type == 'B2B' ? $amountRaw : $totalAmount, // 多項商品時，以「|」分開，若三聯式須為稅前價格
    'ItemAmt' => $type == 'B2B' ? $amountRaw : $totalAmount, // 多項商品時，以「|」分開，若三聯式須為稅前價格
    'Comment' => '備註',
]);

// $invoice->create([
//     'Status' => '1', // 1=立即開立，0=待開立，3=延遲開立
//     'CreateStatusTime' => null, // Status = 3 時設置
//     // 'MerchantOrderNo' => time(),
//     'MerchantOrderNo' => '2', // 測試重複開立
//     'BuyerName' => '王大品',
//     'BuyerUBN' => '54352706',
//     'BuyerAddress' => '台北市南港區南港路二段 97 號 8 樓',
//     'BuyerEmail' => '54352706@pay2go.com',
//     'Category' => 'B2B', // 二聯 B2C，三聯 B2B
//     'TaxType' => '1',
//     'TaxRate' => '5',
//     'Amt' => '476',
//     'TaxAmt' => '24',
//     'TotalAmt' => '500',
//     'PrintFlag' => 'Y',
//     'ItemName' => '商品一|商品二', // 多項商品時，以「|」分開
//     'ItemCount' => '1|2', // 多項商品時，以「|」分開
//     'ItemUnit' => '個|個', // 多項商品時，以「|」分開
//     'ItemPrice' => '476|100', // 多項商品時，以「|」分開，未稅價格
//     'ItemAmt' => '476|200', // 多項商品時，以「|」分開，未稅價格
//     'Comment' => '備註',
// ]);

dd(
    $invoice->isOK(),
    $invoice->getResponse(),
    $invoice->getResult(),
    $invoice->getErrorMessage()
);