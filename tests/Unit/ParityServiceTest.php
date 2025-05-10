<?php

namespace Tests\Unit;

use App\Services\ParityService;
use PHPUnit\Framework\TestCase;

class ParityServiceTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_example(): void
    {
        $parityService = new ParityService();
        $input = [1, 2, 3, 4, 5];
        $result = $parityService->parity($input);
        $this->assertEquals([0, 0, 1, 1, 1], $result);
    }
}
