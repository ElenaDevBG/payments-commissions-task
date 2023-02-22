<?php

declare(strict_types=1);

namespace App\Service;

class Math
{
    private $scale;

    public function __construct(int $scale)
    {
        $this->scale = $scale;
    }

    public function add(string $leftOperand, string $rightOperand): float
    {
        return (float)bcadd($leftOperand, $rightOperand, $this->scale);
    }

    public function subtract(string $leftOperand, string $rightOperand): float
    {
        return (float)bcsub($leftOperand, $rightOperand, $this->scale);
    }

    public function multiply(string $leftOperand, string $rightOperand): float
    {
        return (float)bcmul($leftOperand, $rightOperand, $this->scale);
    }

    public function divide(string $leftOperand, string $rightOperand): float
    {
        return (float)bcdiv($leftOperand, $rightOperand, $this->scale);
    }
}
