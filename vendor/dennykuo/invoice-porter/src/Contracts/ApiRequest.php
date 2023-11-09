<?php

namespace InvoicePorter\Contracts;

interface ApiRequest
{
    public function send(string $apiUri, array $postData);
}