<?php

require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';

use InvoicePorter\EzpayInvoice;

$account = (require '../config/ezpay-invoice.php')['testing'];
$invoice = new EzpayInvoice($account, $isProduction = false);

$invoice = $invoice->info([
    'DisplayFlag' => 1,
    'SearchType' => 0,
    'MerchantOrderNo' => '1589331622',
    'InvoiceNumber' => 'AA00000076',
    'RandomNum' => '0991',
    'TotalAmt' => 500, // 選擇性
]);

// 將直接轉至系統商頁面