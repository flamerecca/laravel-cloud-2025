<?php

namespace Tests\Unit;

use App\Services\CountService;
use DivisionByZeroError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CountServiceTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    #[DataProvider('provider')] public function test_add_number($expected, $num1, $num2): void
    {
        $target = new CountService();
        if (!is_null($num2)) {
            $this->assertEquals($expected, $target->add($num1, $num2));
        } else {
            $this->assertEquals($expected, $target->add($num1));
        }
    }


    public static function provider(): array
    {
        return [
            [5, 2, 3],
            [3, 3, 0],
            [0, 3, -3],
            [3, 2, null],
            [0, -1, null],
            [-1, -2, null],
        ];
    }

    #[DataProvider('providerFloat')] public function test_add_float_number($expected, $num1, $num2): void
    {
        $target = new CountService();
        if (!is_null($num2)) {
            $this->assertEquals($expected, new CountService()->addFloat($num1, $num2)); //
        } else {
            $this->assertEquals($expected, new CountService()->addFloat($num1));
        }
    }

    public static function providerFloat(): array
    {
        return [
            [5.0, 2.0, 3.0],
            [3.0, 3.0, 0],
            [0, 3.0, -3.0],
            [3.0, 2.0, null],
            [0, -1.0, null],
            [-1.0, -2.0, null],
        ];
    }

    /**
     * A basic feature test example.
     */
    #[DataProvider('divide_provider')] public function test_divide_number($expected, $num1, $num2): void
    {
        $target = new CountService();
        if (!is_null($num2)) {
            $this->assertEquals($expected, $target->divide($num1, $num2));
        } else {
            $this->assertEquals($expected, $target->divide($num1));
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
