<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

use App\ExchangeRate;

class ExchangeRateTest extends TestCase
{
    public function testIndex()
    {
        $filename = "./uploads/file.csv";

        $this->assertFileExists(
            $filename,
            "file not found"
        );

        $expectedArray = ["0.60", "3.00", "0.00", "0.06", "1.50", "0.00", "0.69", "0.30", "0.30", "3.00", "0.00", "0.00", "8,607.39"];

        $index = new ExchangeRate($filename);
        $test = implode("\n", $expectedArray);
        $test = $test . "\n";

        $index->index();

        $this->expectOutputString($test);
    }
}
