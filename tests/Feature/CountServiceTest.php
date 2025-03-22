<?php

namespace Tests\Feature;

use App\Services\CountService;
use DivisionByZeroError;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CountServiceTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    #[DataProvider('provider')] public function test_add_number($expected, $a, $b): void
    {
        $target = new CountService();
        if (!is_null($b)) {
            $this->assertEquals($expected, $target->add($a, $b));
        } else {
            $this->assertEquals($expected, $target->add($a));
        }
    }


    public static function provider(): array
    {
        return [
            [5, 2, 3],
            [3, 2, null],
            [0, -1, null],
            [-1, -2, null],
        ];
    }

    /**
     * A basic feature test example.
     */
    #[DataProvider('divide_provider')] public function test_divide_number($expected, $a, $b): void
    {
        $target = new CountService();
        if (!is_null($b)) {
            $this->assertEquals($expected, $target->divide($a, $b));
        } else {
            $this->assertEquals($expected, $target->divide($a));
        }
    }

    public static function divide_provider(): array
    {
        return [
            [0.6666666666666666, 2, 3],
            [2, 2, null],
            [-2, -2, null],
        ];
    }

    /**
     * A basic feature test example.
     *
     */
    public function test_divide_by_zero_number(): void
    {
        $this->expectException(DivisionByZeroError::class);
        $target = new CountService();
        $target->divide(3, 0);
    }
}
