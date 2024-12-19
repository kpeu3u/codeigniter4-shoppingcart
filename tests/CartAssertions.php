<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Assert;
use ShoppingCart\Cart;

trait CartAssertions
{
    /**
     * Assert that cart containts the given number of items.
     *
     * @param float|int $items
     */
    public function assertItemsInCart($items, Cart $cart): void
    {
        $actual = $cart->count();

        Assert::assertEquals($items, $cart->count(), "Expected the cart to contain {$items} items, but got {$actual}.");
    }

    /**
     * Assert that the cart contains the given number of rows.
     */
    public function assertRowsInCart(int $rows, Cart $cart): void
    {
        $actual = $cart->content()->count();

        Assert::assertCount($rows, $cart->content(), "Expected the cart to contain {$rows} rows, but got {$actual}.");
    }
}
