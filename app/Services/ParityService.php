<?php

namespace App\Services;

class ParityService
{
    public function __invoke(array $nums): array
    {
        $transformed = array_map(function ($num) {
            return $num % 2 === 0 ? 0 : 1;
        }, $nums);

        sort($transformed);
        return $transformed;
    }
}
