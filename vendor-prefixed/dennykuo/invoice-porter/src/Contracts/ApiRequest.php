<?php

namespace Woomp\InvoicePorter\Contracts;

interface ApiRequest
{
    public function send(string $apiUri, array $postData);
}