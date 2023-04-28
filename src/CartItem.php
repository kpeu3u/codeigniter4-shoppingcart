<?php

namespace ShoppingCart;

use ShoppingCart\Contracts\Buyable;
use Tightenco\Collect\Contracts\Support\Arrayable;
use Tightenco\Collect\Contracts\Support\Jsonable;
use Tightenco\Collect\Support\Arr;

class CartItem implements Arrayable, Jsonable
{
    /**
     * The rowID of the cart item.
     *
     * @var string
     */
    public string $rowId;

    /**
     * The ID of the cart item.
     *
     * @var int|string
     */
    public $id;

    /**
     * The quantity for this cart item.
     *
     * @var int|float
     */
    public $qty;

    /**
     * The name of the cart item.
     *
     * @var string
     */
    public string $name;

    /**
     * The price without TAX of the cart item.
     *
     * @var float
     */
    public $price;

    /**
     * The options for this cart item.
     *
     * @var object
     */
    public $options;

    /**
     * The FQN of the associated model.
     *
     * @var string|null
     */
    private ?string $associatedModel = null;

    /**
     * The tax rate for the cart item.
     *
     * @var int|float
     */
    private $taxRate = 0;

    /**
     * Is item saved for later.
     *
     * @var boolean
     */
    private bool $isSaved = false;

    /**
     * CartItem constructor.
     *
     * @param int|string $id
     * @param string $name
     * @param float $price
     * @param array      $options
     */
    public function __construct($id, string $name, float $price, array $options = [])
    {
        if (empty($id)) {
            throw new \InvalidArgumentException('Please supply a valid identifier.');
        }

        if (empty($name)) {
            throw new \InvalidArgumentException('Please supply a valid name.');
        }

        if (!is_numeric($price)) {
            throw new \InvalidArgumentException('Please supply a valid price.');
        }

        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
        $this->options = new CartItemOptions($options);
        $this->rowId = static::generateRowId($id, $options);
    }

    /**
     * Return the formatted price without TAX.
     *
     * @param int|null $decimals
     * @param string|null $decimalPoint
     * @param string|null $thousandSeparator
     * @return string
     */
    public function price(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        return static::numberFormat($this->price, $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Return the formatted price with TAX.
     *
     * @param int|null $decimals
     * @param string|null $decimalPoint
     * @param string|null $thousandSeparator
     * @return string
     */
    public function priceTax(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        return static::numberFormat($this->priceTax, $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Returns the formatted subTotal.
     *
     * @param int|null $decimals
     * @param string|null $decimalPoint
     * @param string|null $thousandSeparator
     * @return string
     */
    public function subTotal(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        return static::numberFormat($this->subTotal, $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Returns the formatted total.
     *
     * @param int|null $decimals
     * @param string|null $decimalPoint
     * @param string|null $thousandSeparator
     * @return string
     */
    public function total(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        return static::numberFormat($this->total, $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Returns the formatted tax.
     *
     * @param int|null $decimals
     * @param string|null $decimalPoint
     * @param string|null $thousandSeparator
     * @return string
     */
    public function tax(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        return static::numberFormat($this->tax, $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Returns the formatted tax.
     *
     * @param int|null $decimals
     * @param string|null $decimalPoint
     * @param string|null $thousandSeparator
     * @return string
     */
    public function taxTotal(?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        return static::numberFormat($this->taxTotal, $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Set the quantity for the cart item.
     *
     * @param int|float $qty
     * @return void
     */
    public function setQuantity($qty)
    {
        if (empty($qty) || !is_numeric($qty)) {
            throw new \InvalidArgumentException('Please supply a valid quantity.');
        }

        $this->qty = $qty;
    }

    /**
     * Update the cart item from a buyable.
     *
     * @param Buyable $item
     * @return void
     */
    public function updateFromBuyable(Buyable $item)
    {
        $this->id = $item->getBuyableIdentifier($this->options);
        $this->name = $item->getBuyableDescription($this->options);
        $this->price = $item->getBuyablePrice($this->options);
        $this->priceTax = $this->price + $this->tax;
    }

    /**
     * Update the cart item from an array.
     *
     * @param array $attributes
     * @return void
     */
    public function updateFromArray(array $attributes)
    {
        $this->id = Arr::get($attributes, 'id', $this->id);
        $this->qty = Arr::get($attributes, 'qty', $this->qty);
        $this->name = Arr::get($attributes, 'name', $this->name);
        $this->price = Arr::get($attributes, 'price', $this->price);
        $this->options = new CartItemOptions(Arr::get($attributes, 'options', $this->options));
        $this->priceTax = $this->price + $this->tax;

        $this->rowId = $this->generateRowId($this->id, $this->options->all());
    }

    /**
     * Associate the cart item with the given model.
     *
     * @param mixed $model
     * @return CartItem
     */
    public function associate($model): CartItem
    {
        $this->associatedModel = is_string($model) ? $model : get_class($model);

        return $this;
    }

    /**
     * Set the tax rate.
     *
     * @param int|float $taxRate
     * @return CartItem
     */
    public function setTaxRate($taxRate): CartItem
    {
        $this->taxRate = $taxRate;

        return $this;
    }

    /**
     * Set saved state.
     *
     * @param bool $bool
     * @return CartItem
     */
    public function setSaved($bool): CartItem
    {
        $this->isSaved = $bool;

        return $this;
    }

    /**
     * Get an attribute from cart item or get the associated model.
     *
     * @param $attribute
     * @return mixed
     */
    public function __get($attribute)
    {
        if (property_exists($this, $attribute)) {
            return $this->{$attribute};
        }

        if ($attribute === 'priceTax') {
            return number_format(($this->price + $this->tax), 2, '.', '');
        }

        if ($attribute === 'subtotal') {
            return number_format(($this->qty * $this->price), 2, '.', '');
        }

        if ($attribute === 'total') {
            return number_format(($this->qty * $this->priceTax), 2, '.', '');
        }

        if ($attribute === 'tax') {
            return number_format(($this->price * ($this->taxRate / 100)), 2, '.', '');
        }

        if ($attribute === 'taxTotal') {
            return number_format(($this->tax * $this->qty), 2, '.', '');
        }

        if ($attribute === 'model' && isset($this->associatedModel)) {
            return with(new $this->associatedModel())->find($this->id);
        }

        return null;
    }

    /**
     * Create a new instance from a Buyable.
     *
     * @param Buyable $item
     * @param array                                         $options
     * @return CartItem
     */
    public static function fromBuyable(Buyable $item, array $options = []): CartItem
    {
        return new self($item->getBuyableIdentifier($options), $item->getBuyableDescription($options), $item->getBuyablePrice($options), $options);
    }

    /**
     * Create a new instance from the given array.
     *
     * @param array $attributes
     * @return CartItem
     */
    public static function fromArray(array $attributes): CartItem
    {
        $options = Arr::get($attributes, 'options', []);

        return new self($attributes['id'], $attributes['name'], $attributes['price'], $options);
    }

    /**
     * Create a new instance from the given attributes.
     *
     * @param int|string $id
     * @param string $name
     * @param float $price
     * @param array      $options
     * @return CartItem
     */
    public static function fromAttributes($id, string $name, float $price, array $options = []): CartItem
    {
        return new self($id, $name, $price, $options);
    }

    /**
     * Generate a unique id for the cart item.
     *
     * @param string $id
     * @param array  $options
     * @return string
     */
    protected static function generateRowId(string $id, array $options): string
    {
        ksort($options);

        return md5($id . serialize($options));
    }

    /**
     * Get the instance as an array.
     *
     * @return array
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
            'tax'      => $this->tax,
            'isSaved'  => $this->isSaved,
            'subTotal' => $this->subTotal,
        ];
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options
     * @return string
     */
    public function toJson($options = 0): string
    {
        if (isset($this->associatedModel)) {
            return json_encode(array_merge($this->toArray(), ['model' => $this->model]), $options);
        }

        return json_encode($this->toArray(), $options);
    }

    /**
     * Get the formatted number.
     *
     * @param float $value
     * @param int|null $decimals
     * @param string|null $decimalPoint
     * @param string|null $thousandSeparator
     * @return string
     */
    public static function numberFormat(float $value, ?int $decimals = null, ?string $decimalPoint = null, ?string $thousandSeparator = null): string
    {
        if (is_null($decimals)) {
            $decimals = config('Cart')->format['decimals'] ?? 2;
        }

        if (is_null($decimalPoint)) {
            $decimalPoint = config('Cart')->format['decimal_point'] ?? '.';
        }

        if (is_null($thousandSeparator)) {
            $thousandSeparator = config('Cart')->format['thousand_separator'] ?? ',';
        }

        return number_format($value, $decimals, $decimalPoint, $thousandSeparator);
    }
}
