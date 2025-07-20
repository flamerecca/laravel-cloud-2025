<?php

namespace App\Services;

class CountService
{
    public function add(int $num1, int $num2 = 1): int
    {
        return $num1 + $num2;
    }

    public function divide(int $num1, int $num2 = 1): float|int
    {
        return $num1 / $num2;
    }
}
