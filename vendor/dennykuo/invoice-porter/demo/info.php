<?php

require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';

use InvoicePorter\EzpayInvoice;

$account = (require '../config/ezpay-invoice.php')['testing'];
$invoice = new EzpayInvoice($account, $isProduction = false);

$invoice = $invoice->info([
    'SearchType' => 0,
    'MerchantOrderNo' => '1589331622',
    'InvoiceNumber' => 'AA00000076',
    'RandomNum' => '0991',
    'TotalAmt' => 500, // 選擇性
]);

dd(
    $invoice->isOK(),
    $invoice->getResponse(),
    $invoice->getResult(),
    $invoice->getErrorMessage()
);