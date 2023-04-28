<?php

declare(strict_types=1);

namespace ShoppingCart\Contracts;

interface Buyable
{
    /**
     * Get the identifier of the Buyable item.
     *
     * @param mixed|null $options
     *
     * @return int|string
     */
    public function getBuyableIdentifier($options = null);

    /**
     * Get the description or title of the Buyable item.
     *
     * @param mixed|null $options
     */
    public function getBuyableDescription($options = null): string;

    /**
     * Get the price of the Buyable item.
     *
     * @param mixed|null $options
     */
    public function getBuyablePrice($options = null): float;
}
