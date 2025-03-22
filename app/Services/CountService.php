<?php

namespace App\Services;

class CountService
{
    public function add(int $a, int $b = 1): int
    {
        return $a + $b;
    }

    public function divide(int $a, int $b = 1): float|int
    {
        return $a / $b;
    }
}
