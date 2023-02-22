<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Math;

class Calculation extends Math
{
    private $scale;

    public function __construct(int $scale)
    {
        $this->scale = $scale;
        parent::__construct($this->scale);
    }

    protected function calculateFeeAmount($clientFee, $amount)
    {
        $chargedFee = $this->multiply("$clientFee", "$amount");
        return number_format(round($chargedFee, 2), 2) . "\n";
    }

    protected function convertToEuro($transactionAmount, $exchangeRateCurrencyAmount)
    {
        $currToEuro = $this->divide("1", "$exchangeRateCurrencyAmount");
        return $this->multiply("$transactionAmount", "$currToEuro");
    }
}
