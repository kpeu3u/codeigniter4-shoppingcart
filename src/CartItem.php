<?php

declare(strict_types=1);

namespace ShoppingCart;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use ShoppingCart\Contracts\Buyable;

class CartItem implements Arrayable, Jsonable
{
    /**
     * The rowID of the cart item.
     */
    public string $rowId;

    /**
     * The ID of the cart item.
     */
    public mixed $id;

    /**
     * The quantity for this cart item.
     */
    public int $qty;

    /**
     * The name of the cart item.
     */
    public string $name;

    /**
     * The price without TAX of the cart item.
     */
    public mixed $price;

    /**
     * The options for this cart item.
     */
    public object $options;

    /**
     * The FQN of the associated model.
     */
    private ?string $associatedModel = null;

    /**
     * The tax rate for the cart item.
     */
    private float|int $taxRate = 0;

    /**
     * Is item saved for later.
     */
    private bool $isSaved = false;

    /**
     * CartItem constructor.
     */
    public function __construct(mixed $id, ?string $name, mixed $price, array $options = [])
    {
        if (empty($id)) {
            throw new InvalidArgumentException('Please supply a valid identifier.');
        }

        if (empty($name)) {
            throw new InvalidArgumentException('Please supply a valid name.');
        }

        if (! is_numeric($price)) {
            throw new InvalidArgumentException('Please supply a valid price.');
        }

        $this->id      = $id;
        $this->name    = $name;
        $this->price   = $price;
        $this->options = new CartItemOptions($options);
        $this->rowId   = static::generateRowId($id, $options);
    }

    public function getAssociatedModel(): ?string
    {
        return $this->associatedModel;
    }

    /**
     * Return the formatted price without TAX.
     */
    public function price(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        return static::numberFormat((float) $this->price, $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Return the formatted price with TAX.
     */
    public function priceTax(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        return static::numberFormat((float) $this->priceTax, $decimals, $decimalPoint, $thousandSeparator); // @phpstan-ignore-line
    }

    /**
     * Returns the formatted subTotal.
     */
    public function subTotal(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        return static::numberFormat((float) $this->subTotal, $decimals, $decimalPoint, $thousandSeparator); // @phpstan-ignore-line
    }

    /**
     * Returns the formatted total.
     */
    public function total(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        return static::numberFormat((float) $this->total, $decimals, $decimalPoint, $thousandSeparator); // @phpstan-ignore-line
    }

    /**
     * Returns the formatted tax.
     */
    public function tax(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        return static::numberFormat((float) $this->tax, $decimals, $decimalPoint, $thousandSeparator); // @phpstan-ignore-line
    }

    /**
     * Returns the formatted tax.
     */
    public function taxTotal(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        return static::numberFormat((float) ($this->taxTotal), $decimals, $decimalPoint, $thousandSeparator); // @phpstan-ignore-line
    }

    /**
     * Set the quantity for the cart item.
     */
    public function setQuantity(mixed $qty): void
    {
        if (empty($qty) || ! is_numeric($qty)) {
            throw new InvalidArgumentException('Please supply a valid quantity.');
        }

        $this->qty = $qty;
    }

    /**
     * Update the cart item from a buyable.
     */
    public function updateFromBuyable(Buyable $item): void
    {
        $this->id       = $item->getBuyableIdentifier($this->options);
        $this->name     = $item->getBuyableDescription($this->options);
        $this->price    = $item->getBuyablePrice($this->options);
        $this->priceTax = $this->price + $this->tax; // @phpstan-ignore-line
    }

    /**
     * Update the cart item from an array.
     */
    public function updateFromArray(array $attributes): void
    {
        $this->id       = Arr::get($attributes, 'id', $this->id);
        $this->qty      = Arr::get($attributes, 'qty', $this->qty);
        $this->name     = Arr::get($attributes, 'name', $this->name);
        $this->price    = Arr::get($attributes, 'price', $this->price);
        $this->options  = new CartItemOptions(Arr::get($attributes, 'options', $this->options));
        $this->priceTax = $this->price + $this->tax; // @phpstan-ignore-line

        $this->rowId = static::generateRowId($this->id, $this->options->all());
    }

    /**
     * Associate the cart item with the given model.
     */
    public function associate(mixed $model): CartItem
    {
        $this->associatedModel = is_string($model) ? $model : $model::class;

        return $this;
    }

    /**
     * Set the tax rate.
     */
    public function setTaxRate(float|int $taxRate): CartItem
    {
        $this->taxRate = $taxRate;

        return $this;
    }

    /**
     * Set saved state.
     */
    public function setSaved(bool $bool): CartItem
    {
        $this->isSaved = $bool;

        return $this;
    }

    /**
     * Get an attribute from cart item or get the associated model.
     *
     * @return mixed
     */
    public function __get(mixed $attribute)
    {
        if (property_exists($this, $attribute)) {
            return $this->{$attribute};
        }

        if ($attribute === 'priceTax') {
            return number_format(($this->price + $this->tax), 2, '.', ''); // @phpstan-ignore-line
        }

        if ($attribute === 'subTotal') {
            return number_format(($this->qty * $this->price), 2, '.', '');
        }

        if ($attribute === 'total') {
            return number_format(($this->qty * $this->priceTax), 2, '.', ''); // @phpstan-ignore-line
        }

        if ($attribute === 'tax') {
            return number_format(($this->price * ($this->taxRate / 100)), 2, '.', '');
        }

        if ($attribute === 'taxTotal') {
            return number_format(($this->tax * $this->qty), 2, '.', ''); // @phpstan-ignore-line
        }

        if ($attribute === 'model' && isset($this->associatedModel)) {
            return (new $this->associatedModel())->find($this->id);
        }

        return null;
    }

    /**
     * Create a new instance from a Buyable.
     */
    public static function fromBuyable(Buyable $item, array $options = []): CartItem
    {
        return new self($item->getBuyableIdentifier($options), $item->getBuyableDescription($options), $item->getBuyablePrice($options), $options);
    }

    /**
     * Create a new instance from the given array.
     */
    public static function fromArray(array $attributes): CartItem
    {
        $options = Arr::get($attributes, 'options', []);

        return new self($attributes['id'], $attributes['name'], $attributes['price'], $options);
    }

    /**
     * Create a new instance from the given attributes.
     */
    public static function fromAttributes(mixed $id, mixed $name, mixed $price, array $options = []): CartItem
    {
        return new self($id, $name, $price, $options);
    }

    /**
     * Generate a unique id for the cart item.
     */
    protected static function generateRowId(int|string $id, array $options): string
    {
        ksort($options);

        return md5($id . serialize($options));
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'rowId'    => $this->rowId,
            'id'       => $this->id,
            'name'     => $this->name,
            'qty'      => $this->qty,
            'price'    => $this->price,
            'options'  => $this->options->toArray(),
            'tax'      => $this->tax, // @phpstan-ignore-line
            'isSaved'  => $this->isSaved,
            'subTotal' => $this->subTotal, // @phpstan-ignore-line
        ];
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options
     */
    public function toJson($options = 0): string
    {
        if (isset($this->associatedModel)) {
            return json_encode(array_merge($this->toArray(), ['model' => $this->model]), $options); // @phpstan-ignore-line
        }

        return json_encode($this->toArray(), $options);
    }

    /**
     * Get the formatted number.
     */
    public static function numberFormat(float $value, ?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        if (null === $decimals) {
            $decimals = config('Cart')->format['decimals'] ?? 2;
        }

        if (null === $decimalPoint) {
            $decimalPoint = config('Cart')->format['decimal_point'] ?? '.';
        }

        if (null === $thousandSeparator) {
            $thousandSeparator = config('Cart')->format['thousand_separator'] ?? ',';
        }

        return number_format($value, $decimals, $decimalPoint, $thousandSeparator);
    }
}
