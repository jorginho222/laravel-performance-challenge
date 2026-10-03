<?php

namespace Tests\Unit\Shared;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Shared\Domain\Money;

class MoneyTest extends TestCase
{
    #[DataProvider('decimals')]
    public function test_it_parses_decimal_amounts(string $amount, int $cents, string $decimal): void
    {
        $money = Money::fromDecimal($amount);

        $this->assertSame($cents, $money->cents);
        $this->assertSame($decimal, $money->toDecimal());
    }

    /**
     * @return array<string, array{string, int, string}>
     */
    public static function decimals(): array
    {
        return [
            'two decimals' => ['19.99', 1999, '19.99'],
            'one decimal' => ['5.5', 550, '5.50'],
            'no decimals' => ['3', 300, '3.00'],
            'cents only' => ['0.05', 5, '0.05'],
        ];
    }

    #[DataProvider('invalidAmounts')]
    public function test_it_rejects_invalid_amounts(string $amount): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromDecimal($amount);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidAmounts(): array
    {
        return [
            'negative' => ['-1.00'],
            'three decimals' => ['1.999'],
            'not a number' => ['abc'],
        ];
    }

    public function test_arithmetic_is_exact(): void
    {
        $total = Money::fromDecimal('0.10')->multiply(3)->add(Money::fromDecimal('0.20'));

        $this->assertSame('0.50', $total->toDecimal());
    }
}
