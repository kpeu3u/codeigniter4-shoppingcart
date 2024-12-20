<?php

declare(strict_types=1);

namespace Tests;

class ProductModel
{
    public string $someValue = 'Some value';

    public function find(): ProductModel
    {
        return $this;
    }
}
