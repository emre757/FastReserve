<?php

namespace App\Payments;

use App\Enums\Currency;
use InvalidArgumentException;

// Money data representing it in minor units with currency attached
final readonly class Money
{
    private const int MINOR_UNIT_FACTOR = 100;

    private function __construct(
        public int $minorUnits,
        public Currency $currency,
    ) {
        if ($minorUnits < 0) {
            throw new InvalidArgumentException(
                'Money amount cannot be negative.',
            );
        }
    }

    public static function fromDecimal(
        string $amount,
        Currency $currency,
    ): self {
        $amount = trim($amount);

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)) {
            throw new InvalidArgumentException(
                'Amount must have no more than two decimal places.',
            );
        }

        [$wholeUnits, $decimalUnits] = array_pad(
            explode('.', $amount, 2),
            2,
            '',
        );

        $minorUnits = ((int) $wholeUnits * self::MINOR_UNIT_FACTOR)
            + (int) str_pad($decimalUnits, 2, '0');

        return new self($minorUnits, $currency);
    }

    public static function fromMinorUnits(
        int $minorUnits,
        Currency $currency,
    ): self {
        return new self($minorUnits, $currency);
    }

    public function multiply(int $quantity): self
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException(
                'Quantity must be at least one.',
            );
        }

        return new self(
            $this->minorUnits * $quantity,
            $this->currency,
        );
    }

    public function decimal(): string
    {
        return sprintf(
            '%d.%02d',
            intdiv($this->minorUnits, self::MINOR_UNIT_FACTOR),
            $this->minorUnits % self::MINOR_UNIT_FACTOR,
        );
    }
}
