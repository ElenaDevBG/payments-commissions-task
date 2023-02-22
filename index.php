<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\ExchangeRate;

$outputObject = new ExchangeRate('uploads/file.csv');
$outputObject->index();
