<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    #[DataProvider('amounts')]
    public function testParsesDecimalStringsToCents(string $input, int $cents): void
    {
        self::assertSame($cents, Money::toCents($input));
    }

    public static function amounts(): array
    {
        return [['0', 0], ['12', 1200], ['12.5', 1250], ['12.05', 1205], ['0.99', 99], ['19.99', 1999]];
    }

    public function testRejectsInvalidAmounts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::toCents('12.999');
    }

    public function testFormatsAndRoundTrips(): void
    {
        self::assertSame('$1,234.05', Money::format(123405));
        self::assertSame('12.05', Money::toDecimal(1205));
        self::assertSame(1999, Money::toCents(Money::toDecimal(1999)));
    }
}
