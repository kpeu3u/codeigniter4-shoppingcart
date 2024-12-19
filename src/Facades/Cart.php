<?php

declare(strict_types=1);

namespace ShoppingCart\Facades;

use Closure;
use ShoppingCart\Cart as ShoppingCart;

/**
 * @method static ShoppingCart __get($attribute)
 * @method static ShoppingCart add($id, $name = null, $qty = null, $price = null, array $options = [], $taxrate = null)
 * @method static ShoppingCart associate($rowId, $model)
 * @method static ShoppingCart content()
 * @method static ShoppingCart count()
 * @method static ShoppingCart currentInstance()
 * @method static ShoppingCart destroy()
 * @method static ShoppingCart get($rowId)
 * @method static ShoppingCart instance($instance = null)
 * @method static ShoppingCart remove($rowId)
 * @method static ShoppingCart restore($identifier)
 * @method static ShoppingCart search(Closure $search)
 * @method static ShoppingCart setTax($rowId, $taxRate)
 * @method static ShoppingCart store($identifier)
 * @method static ShoppingCart subtotal($decimals = null, $decimalPoint = null, $thousandSeparator = null)
 * @method static ShoppingCart tax($decimals = null, $decimalPoint = null, $thousandSeparator = null)
 * @method static ShoppingCart total($decimals = null, $decimalPoint = null, $thousandSeparator = null)
 * @method static ShoppingCart update($rowId, $qty)
 *
 * @see ShoppingCart
 */
class Cart
{
    /**
     * @return ShoppingCart
     */
    public static function __callStatic(mixed $method, mixed $arguments)
    {
        return (new ShoppingCart())->{$method}(...$arguments);
    }
}
