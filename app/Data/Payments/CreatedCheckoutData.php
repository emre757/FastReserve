<?php

namespace App\Data\Payments;

final readonly class CreatedCheckoutData
{
    public function __construct(
        public ?string $id,
        public string $url,
    ) {}
}
