<?php

declare(strict_types=1);

namespace Tests;

use ShoppingCart\Contracts\Buyable;

class BuyAbleProduct implements Buyable
{
    public function __construct(private readonly int|string $id = 1, private readonly string $name = 'Item name', private readonly float $price = 10.00)
    {
    }

    public function getBuyableIdentifier(mixed $options = null): int|string
    {
        return $this->id;
    }

    public function getBuyableDescription(mixed $options = null): string
    {
        return $this->name;
    }

    public function getBuyablePrice(mixed $options = null): float
    {
        return $this->price;
    }
}
