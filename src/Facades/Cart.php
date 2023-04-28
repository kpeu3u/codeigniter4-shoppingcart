<?php

namespace ShoppingCart\Facades;

use ShoppingCart\Cart as ShoppingCart;

/**
 * @method static ShoppingCart instance($instance = null)
 * @method static ShoppingCart currentInstance()
 * @method static ShoppingCart add($id, $name = null, $qty = null, $price = null, array $options = [], $taxrate = null)
 * @method static ShoppingCart update($rowId, $qty)
 * @method static ShoppingCart remove($rowId)
 * @method static ShoppingCart get($rowId)
 * @method static ShoppingCart destroy()
 * @method static ShoppingCart content()
 * @method static ShoppingCart count()
 * @method static ShoppingCart total($decimals = null, $decimalPoint = null, $thousandSeparator = null)
 * @method static ShoppingCart tax($decimals = null, $decimalPoint = null, $thousandSeparator = null)
 * @method static ShoppingCart subtotal($decimals = null, $decimalPoint = null, $thousandSeparator = null)
 * @method static ShoppingCart search(\Closure $search)
 * @method static ShoppingCart associate($rowId, $model)
 * @method static ShoppingCart setTax($rowId, $taxRate)
 * @method static ShoppingCart store($identifier)
 * @method static ShoppingCart restore($identifier)
 * @method static ShoppingCart __get($attribute)
 * 
 * @see ShoppingCart
 */
class Cart
{
    /**
     * @param $method
     * @param $arguments
     * @return ShoppingCart
     */
    public static function __callStatic($method, $arguments)
    {
        return (new ShoppingCart())->$method(...$arguments);
    }
}