<?php

declare(strict_types=1);

namespace ShoppingCart\Config;

use CodeIgniter\Config\BaseService;
use ShoppingCart\Cart;

class Services extends BaseService
{
    public static function cart($getShared = true): object
    {
        if ($getShared) {
            return static::getSharedInstance('cart');
        }

        return new Cart();
    }
}
