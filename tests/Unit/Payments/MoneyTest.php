<?php

use App\Enums\Currency;
use App\Payments\Money;

it('throws on negative amounts', function () {
    $currency = Currency::USD;
    expect(fn () => Money::fromDecimal('-10', $currency))->toThrow(InvalidArgumentException::class);
});

describe('fromDecimal function', function () {
    it('should create a Money object from a decimal value', function (string $amount, int $expectedMinorUnits) {
        $currency = Currency::USD;
        $money = Money::fromDecimal($amount, $currency);

        expect($money->minorUnits)->toBe($expectedMinorUnits)
            ->and($money->currency)->toBe($currency);
    })->with([
        'two decimal places' => ['10.50', 1050],
        'zero' => ['0', 0],
        'whole amount' => ['10', 1000],
        'one decimal place' => ['10.5', 1050],
        'one cent' => ['0.01', 1],
    ]);

    it('should throw with given amount exceeding 2 decimals', function () {
        $currency = Currency::USD;
        expect(fn () => Money::fromDecimal('10.501', $currency))->toThrow(InvalidArgumentException::class);
    });

    it('rejects invalid decimal amounts', function (string $amount) {
        expect(fn () => Money::fromDecimal($amount, Currency::USD))
            ->toThrow(InvalidArgumentException::class);
    })->with([
        'empty string' => [''],
        'string' => ['abc'],
        'comma decimal' => ['10,50'],
    ]);
});

it('should create a Money object from minor units', function (int $minorUnits) {
    $currency = Currency::USD;
    $money = Money::fromMinorUnits($minorUnits, $currency);

    expect($money->minorUnits)->toBe($minorUnits)
        ->and($money->currency)->toBe($currency);
})->with([
    'positive amount' => [1050],
    'zero' => [0],
]);

it('rejects negative minor units', function () {
    expect(fn () => Money::fromMinorUnits(-1, Currency::USD))
        ->toThrow(InvalidArgumentException::class);
});

it('multiplies money object by quantity and is immutable', function (int $quantity, int $expectedMinorUnits) {
    $currency = Currency::USD;
    $money = Money::fromDecimal('10.50', $currency);

    $result = $money->multiply($quantity);

    expect($money->minorUnits)->toBe(1050)
        ->and($money->currency)->toBe($currency)
        ->and($result->minorUnits)->toBe($expectedMinorUnits)
        ->and($result->currency)->toBe($currency);

})->with([
    'multiply by two' => [2, 2100],
    'multiply by one' => [1, 1050],
]);

it('rejects quantities below one', function (int $quantity) {
    $money = Money::fromMinorUnits(1050, Currency::USD);

    expect(fn () => $money->multiply($quantity))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'zero' => [0],
    'negative quantity' => [-1],
]);

test('decimal function', function (int $minorUnits, string $expectedDecimal) {
    $currency = Currency::USD;
    $money = Money::fromMinorUnits($minorUnits, $currency);

    expect($money->decimal())->toBe($expectedDecimal)
        ->and($money->currency)->toBe($currency);
})->with([
    'two decimal places' => [1050, '10.50'],
    'one cent' => [1, '0.01'],
    'one and five cents' => [105, '1.05'],
    'whole amount' => [1000, '10.00'],
    'zero' => [0, '0.00'],
]);
