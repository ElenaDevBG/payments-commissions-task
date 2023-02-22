<?php

namespace App\Traits;

trait DataTrait
{
    private $depositClientFee = 0.0003;

    private $withdrawBusinessClientFee = 0.005;

    private $withdrawPrivateClientFee = 0.003;

    private $clientsWithdraws = [];

    protected function calculateCommissionFeeByOperationType(array $transaction)
    {
        // I'm using BCMath extension for better precision in the calculations
        switch ($transaction['operation_type']) {
            case 'deposit':
                $this->response .= $this->calculateFeeAmount($this->depositClientFee, $transaction['operation_amount']);
                break;
            case 'withdraw':
                $this->calculateWithdrawCommission($transaction);
                break;
        }
    }

    protected function calculateWithdrawCommission(array $transaction)
    {
        $transactionCurrency = $transaction['operation_currency'];
        $userType = $transaction['user_type'];
        $transactionAmount = $transaction['operation_amount'];
        $transactionDate = $transaction['date'];
        $userIdentificatorNumber = $transaction['user_identificator'];

        // Calculate the free of charge amount in USD or JPY if necessary
        $freeOfChargeAmountPerWeek = $transactionCurrency !== 'EUR' ? $this->multiply(1000, $this->{"exchangeRate" . $transactionCurrency}) : 1000.00;

        if ($userType === 'business') {
            $this->response .= $this->calculateFeeAmount($this->withdrawBusinessClientFee, $transactionAmount);
        } else {
            // Find which dates are monday and sunday from transaction's current week
            // Form a key combination from both dates
            $startAndEndOfWeek = $this->findFirstAndLastDatesOfWeek($transactionDate);
            $weekStartAndEnd = $startAndEndOfWeek['monday'] . '_' . $startAndEndOfWeek['sunday'];

            // Calculate the current transaction's amount in EUR if the current currency is different from EUR
            $amountInEuro = $transactionCurrency !== 'EUR' ? $this->convertToEuro($transactionAmount, $this->{"exchangeRate" . $transactionCurrency}) : $transactionAmount;

            // Add transaction's unique information to the array storing client's withdraws
            $this->addClientWithdraw($userIdentificatorNumber, $weekStartAndEnd, $amountInEuro);

            // Get total amount of all transactions of this client for this week in EUR
            $totalWeeklyAmountEU = $transactionCurrency !== 'EUR' ? $this->multiply($this->clientsWithdraws[$userIdentificatorNumber][$weekStartAndEnd]['amount'], $this->{"exchangeRate" . $transactionCurrency}) : $this->clientsWithdraws[$userIdentificatorNumber][$weekStartAndEnd]['amount'];
            // Get the amount of transactions of this client without the current one in EUR
            $amountBeforeTransactionEU = $this->subtract($totalWeeklyAmountEU, $transactionAmount);

            // Calculate fee in case of more than 3 transactions per week or total amount of previous transactions for this same
            // week bigger ot equal than 1000 EUR
            // else if
            // calculate fee in case of total amount of all transactions for this same week (including the current one) are 
            // bigger than 1000 EUR
            if (
                $this->clientsWithdraws[$userIdentificatorNumber][$weekStartAndEnd]['transactions'] > 3 ||
                $amountBeforeTransactionEU >= $freeOfChargeAmountPerWeek
            ) {
                $this->response .= $this->calculateFeeAmount($this->withdrawPrivateClientFee, $transactionAmount);
            } else if ($totalWeeklyAmountEU > $freeOfChargeAmountPerWeek) {
                $chargeBaseAmount = $this->subtract($totalWeeklyAmountEU, $freeOfChargeAmountPerWeek);
                $chargedFee = $this->multiply($this->withdrawPrivateClientFee, $chargeBaseAmount);
                $this->response .= number_format(round($chargedFee, 2), 2) . "\n";
            } else {
                $this->response .= number_format(0, 2) . "\n";
            }
        }
    }

    private function addClientWithdraw($userIdentificatorNumber, $weekStartAndEnd, $amountInEuro)
    {
        if (!isset($this->clientsWithdraws[$userIdentificatorNumber]) || !isset($this->clientsWithdraws[$userIdentificatorNumber][$weekStartAndEnd])) {
            $this->clientsWithdraws[$userIdentificatorNumber][$weekStartAndEnd] = [
                'transactions' => 1,
                'amount' => $amountInEuro
            ];
        } else {
            $this->clientsWithdraws[$userIdentificatorNumber][$weekStartAndEnd] = [
                'transactions' => $this->clientsWithdraws[$userIdentificatorNumber][$weekStartAndEnd]['transactions'] + 1,
                'amount' => $this->clientsWithdraws[$userIdentificatorNumber][$weekStartAndEnd]['amount'] + $amountInEuro
            ];
        }
    }

    private function hasValidData(array $data, int $row, array $errors)
    {
        if (!$this->validateDate($data[0])) {
            $errors[$row][] = 'Invalid date format on row ' . $row;
        }

        if (!is_numeric($data[1])) {
            $errors[$row][] = 'Invalid user identificator number on row ' . $row;
        }

        if (!in_array($data[2], ['private', 'business'])) {
            $errors[$row][] = 'Invalid user type on row ' . $row;
        }

        if (!in_array($data[3], ['deposit', 'withdraw'])) {
            $errors[$row][] = 'Invalid operation type on row ' . $row;
        }

        if (!is_numeric($data[4])) {
            $errors[$row][] = 'Invalid operation amount on row ' . $row;
        }

        if (!in_array($data[5], ['EUR', 'USD', 'JPY'])) {
            $errors[$row][] = 'Invalid operation currency on row ' . $row;
        }

        return empty($errors) || !array_key_exists($row, $errors);
    }

    private function getCurlResponse(string $url)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);

        $exchangeRatesResponse = curl_exec($curl);

        curl_close($curl);

        return is_string($exchangeRatesResponse) ? json_decode($exchangeRatesResponse, false) : [];
    }
}
