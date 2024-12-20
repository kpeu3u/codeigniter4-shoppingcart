<?php

declare(strict_types=1);

namespace ShoppingCart\Config;

use CodeIgniter\Config\BaseConfig;

class Cart extends BaseConfig
{
    /**
     * This default tax rate will be used when you make a class implement the
     * taxable interface and use the HasTax trait.
     */
    public int $tax = 21;

    /**
     * Here you can set the connection that the shopping_cart should use when
     * storing and restoring a cart.
     */
    public string $table = 'shopping_cart';

    /**
     * This defaults will be used for the formatted numbers if you don't
     * set them in the method call.
     */
    public array $format = [
        'decimals' => 2,

        'decimal_point' => '.',

        'thousand_separator' => ',',
    ];
}
