<?php

namespace App;

use App\Service\Calculation;

use App\Traits\DataTrait;
use App\Traits\DateTrait;

class ExchangeRate extends Calculation
{
    use DataTrait;
    use DateTrait;

    private $columnMapping = [
        'date' => 0,
        'user_identificator' => 1,
        'user_type' => 2,
        'operation_type' => 3,
        'operation_amount' => 4,
        'operation_currency' => 5
    ];

    private $filepath;

    private $errors;

    private $response;

    private $exchangeRateUSD = 0;

    private $exchangeRateJPY = 0;

    public function __construct($filepath)
    {
        parent::__construct(9);

        $this->filepath = $filepath;
        $this->errors = [];
        $this->response = '';
    }

    public function index()
    {
        if (!file_exists($this->filepath)) {
            echo 'Error! File not found!';
            exit();
        }

        // Get today's currency exchange rates
        $responseExchangeRate = $this->getCurlResponse('https://developers.paysera.com/tasks/api/currency-exchange-rates');
        $this->exchangeRateUSD = $responseExchangeRate->rates->USD;
        $this->exchangeRateJPY = $responseExchangeRate->rates->JPY;

        $row = 1;
        $transactionsArray = [];

        // Read the csv file
        // Write data to array with named keys
        if (($handle = fopen($this->filepath, "r")) !== false) {
            while (($data = fgetcsv($handle, 100, ",")) !== false) {
                if ($this->hasValidData($data, $row, $this->errors)) {
                    foreach ($this->columnMapping as $field => $column) {
                        if (isset($data[$column])) {
                            $transactionsArray[$row][$field] = trim($data[$column]);
                        }
                    }
                }

                $row++;
            }

            fclose($handle);
        }

        foreach ($transactionsArray as $infoTransaction) {
            $this->calculateCommissionFeeByOperationType($infoTransaction);
        }

        print_r($this->response);

        // Show errors if any
        if (count($this->errors) > 0) {
            echo "\n";
            print_r($this->errors);
        }
    }
}
