<?php

declare(strict_types=1);

namespace ShoppingCart\Contracts;

interface Buyable
{
    /**
     * Get the identifier of the Buyable item.
     */
    public function getBuyableIdentifier(mixed $options = null): int|string;

    /**
     * Get the description or title of the Buyable item.
     */
    public function getBuyableDescription(mixed $options = null): string;

    /**
     * Get the price of the Buyable item.
     */
    public function getBuyablePrice(mixed $options = null): float;
}
