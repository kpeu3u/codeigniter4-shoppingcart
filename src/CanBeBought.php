<?php

declare(strict_types=1);

namespace ShoppingCart;

trait CanBeBought
{
    /**
     * Get the identifier of buyable item.
     */
    public function getBuyableIdentifier(): int|string
    {
        return method_exists($this, 'getKey')
            ? $this->getKey()
            : $this->id;
    }

    /**
     * Get the description or title of the buyable item.
     *
     * @param mixed|null $options
     */
    public function getBuyableDescriptions($options = null): ?string
    {
        if (property_exists($this, 'name')) {
            return $this->name;
        }

        if (property_exists($this, 'title')) {
            return $this->title;
        }

        if (property_exists($this, 'description')) {
            return $this->description;
        }

        return null;
    }

    /**
     * Get the price of buyable item.
     */
    public function getBuyablePrice(): ?float
    {
        return property_exists($this, 'price')
            ? $this->price
            : null;
    }
}
