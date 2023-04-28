<?php

declare(strict_types=1);

namespace ShoppingCart;

use Closure;
use CodeIgniter\Config\Services;
use CodeIgniter\Database\BaseResult;
use CodeIgniter\Events\Events;
use CodeIgniter\I18n\Time;
use CodeIgniter\Session\Session;
use Exception;
use ShoppingCart\Contracts\Buyable;
use ShoppingCart\Exceptions\InvalidRowIDException;
use ShoppingCart\Exceptions\UnknownModelException;
use ShoppingCart\Models\ShoppingCart;
use Tightenco\Collect\Support\Collection;

class Cart
{
    public const DEFAULT_INSTANCE = 'default';

    /**
     * Instance session manager.
     */
    protected Session $session;

    /**
     * Model shopping cart.
     */
    protected ShoppingCart $model;

    protected string $instance;

    /**
     * Cart constructor.
     */
    public function __construct()
    {
        $this->session = Services::session();

        $this->model = new ShoppingCart();

        $this->instance(self::DEFAULT_INSTANCE);
    }

    /**
     * Get the current cart instance.
     *
     * @return $this
     */
    public function instance(?string $instance = null): Cart
    {
        $instance ??= self::DEFAULT_INSTANCE;

        $this->instance = sprintf('%s.%s', 'cart', $instance);

        return $this;
    }

    /**
     * Get the current instance.
     */
    public function currentInstance(): string
    {
        return str_replace('cart.', '', $this->instance);
    }

    /**
     * Add an item to the cart.
     *
     * @param mixed          $id
     * @param mixed          $name
     * @param float|int|null $qty
     * @param mixed|null     $price
     * @param mixed|null     $taxrate
     *
     * @return array|CartItem
     */
    public function add($id, $name = null, $qty = null, $price = null, array $options = [], $taxrate = null)
    {
        if ($this->isMulti($id)) {
            return array_map(fn ($item) => $this->add($item), $id);
        }

        if ($id instanceof CartItem) {
            $cartItem = $id;
        } else {
            $cartItem = $this->createCartItem($id, $name, $qty, $price, $options, $taxrate);
        }

        $content = $this->getContent();

        if ($content->has($cartItem->rowId)) {
            $cartItem->qty += $content->get($cartItem->rowId)->qty;
        }

        $content->put($cartItem->rowId, $cartItem);

        Events::trigger('cart.added', $cartItem);

        $this->session->set($this->instance, $content);

        return $cartItem;
    }

    /**
     * Update the cart item with the given rowId.
     *
     * @param mixed $qty
     * @param mixed $rowId
     *
     * @return CartItem|null
     */
    public function update($rowId, $qty)
    {
        $cartItem = $this->get($rowId);

        if ($qty instanceof Buyable) {
            $cartItem->updateFromBuyable($qty);
        } elseif (is_array($qty)) {
            $cartItem->updateFromArray($qty);
        } else {
            $cartItem->qty = $qty;
        }

        $content = $this->getContent();

        if ($rowId !== $cartItem->rowId) {
            $content->pull($rowId);

            if ($content->has($cartItem->rowId)) {
                $existingCartItem = $this->get($cartItem->rowId);
                $cartItem->setQuantity($existingCartItem->qty + $cartItem->qty);
            }
        }

        if ($cartItem->qty <= 0) {
            $this->remove($cartItem->rowId);

            return null;
        }
        $content->put($cartItem->rowId, $cartItem);

        Events::trigger('cart.updated', $cartItem);

        $this->session->set($this->instance, $content);

        return $cartItem;
    }

    /**
     * Remove the cart item with the given rowId from the cart.
     *
     * @param mixed $rowId
     */
    public function remove($rowId): void
    {
        $cartItem = $this->get($rowId);

        $content = $this->getContent();

        $content->pull($cartItem->rowId);

        Events::trigger('cart.removed', $cartItem);

        $this->session->set($this->instance, $content);
    }

    /**
     * Get a cart item from the cart by its rowId.
     *
     * @param mixed $rowId
     */
    public function get($rowId)
    {
        $content = $this->getContent();

        if (! $content->has($rowId)) {
            throw new InvalidRowIDException("The cart does not contain rowId {$rowId}.");
        }

        return $content->get($rowId);
    }

    /**
     * Destroy the current cart instance.
     */
    public function destroy(): void
    {
        $this->session->remove($this->instance);
    }

    /**
     * Get the content of the cart.
     *
     * @return array|Collection
     */
    public function content()
    {
        if (null === $this->session->get($this->instance)) {
            return new Collection([]);
        }

        return $this->session->get($this->instance);
    }

    /**
     * Get the number of items in the cart.
     *
     * @return float|int
     */
    public function count()
    {
        return $this->getContent()->sum('qty');
    }

    /**
     * Get the total price of the items in the cart.
     *
     * @param int|null    $decimals
     * @param string|null $decimalPoint
     * @param string|null $thousandSeparator
     *
     * @return string
     */
    public function total($decimals = null, $decimalPoint = null, $thousandSeparator = null)
    {
        $content = $this->getContent();

        $total = $content->reduce(static fn ($total, CartItem $cartItem) => $total + ($cartItem->qty * $cartItem->priceTax), 0);

        return CartItem::numberFormat($total, $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Get the total tax of the items in the cart.
     *
     * @param int|null    $decimals
     * @param string|null $decimalPoint
     * @param string|null $thousandSeparator
     *
     */
    public function tax($decimals = null, $decimalPoint = null, $thousandSeparator = null): string
    {
        $content = $this->getContent();

        $tax = $content->reduce(static fn ($tax, CartItem $cartItem) => $tax + ($cartItem->qty * $cartItem->tax), 0);

        return CartItem::numberFormat($tax, $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Get the subtotal (total - tax) of the items in the cart.
     *
     * @param int|null    $decimals
     * @param string|null $decimalPoint
     * @param string|null $thousandSeparator
     *
     * @return string
     */
    public function subtotal($decimals = null, $decimalPoint = null, $thousandSeparator = null): string
    {
        $content = $this->getContent();

        $subTotal = $content->reduce(static fn ($subTotal, CartItem $cartItem) => $subTotal + ($cartItem->qty * $cartItem->price), 0);

        return CartItem::numberFormat($subTotal, $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Search the cart content for a cart item matching the given search closure.
     *
     * @return Collection
     */
    public function search(Closure $search)
    {
        return $this->getContent()->filter($search);
    }

    /**
     * Associate the cart item with the given rowId with the given model.
     *
     * @param string $rowId
     * @param mixed  $model
     */
    public function associate($rowId, $model): void
    {
        if (is_string($model) && ! class_exists($model)) {
            throw new UnknownModelException("The supplied model {$model} does not exist.");
        }

        $cartItem = $this->get($rowId);

        $cartItem->associate($model);

        $content = $this->getContent();

        $content->put($cartItem->rowId, $cartItem);

        $this->session->set($this->instance, $content);
    }

    /**
     * Set the tax rate for the cart item with the given rowId.
     *
     * @param float|int $taxRate
     */
    public function setTax(string $rowId, $taxRate): void
    {
        $cartItem = $this->get($rowId);

        $cartItem->setTaxRate($taxRate);

        $content = $this->getContent();

        $content->put($cartItem->rowId, $cartItem);

        $this->session->set($this->instance, $content);
    }

    /**
     * Store an the current instance of the cart.
     *
     * @param mixed $identifier
     *
     * @throws Exception
     */
    public function store($identifier): void
    {
        $content = $this->getContent();

        $this->model
            ->where('identifier', $identifier)
            ->where('instance', $this->currentInstance())
            ->delete();

        $this->model->insert([
            'identifier' => $identifier,
            'instance'   => $this->currentInstance(),
            'content'    => serialize($content),
            'created_at' => Time::now(),
            'updated_at' => Time::now(),
        ]);

        Events::trigger('cart.stored');
    }

    /**
     * Restore the cart with the given identifier.
     *
     * @param mixed $identifier
     */
    public function restore($identifier): void
    {
        if (! $this->storedCartWithIdentifierExists($identifier)) {
            return;
        }

        $stored = $this->model
            ->where('instance', $this->currentInstance())
            ->where('identifier', $identifier)
            ->first();

        $storedContent = unserialize($stored->content);

        $currentInstance = $this->currentInstance();

        $this->instance($stored->instance);

        $content = $this->getContent();

        foreach ($storedContent as $cartItem) {
            $content->put($cartItem->rowId, $cartItem);
        }

        Events::trigger('cart.restored');

        $this->session->set($this->instance, $content);

        $this->instance($currentInstance);
    }

    /**
     * Deletes the stored cart with given identifier
     *
     * @param mixed $identifier
     *
     * @return BaseResult|bool
     */
    protected function deleteStoredCart($identifier)
    {
        return $this->model->where('identifier', $identifier)->delete();
    }

    /**
     * Magic method to make accessing the total, tax and subtotal properties possible.
     *
     * @param string $attribute
     *
     * @return string|null
     */
    public function __get($attribute)
    {
        if ($attribute === 'total') {
            return $this->total();
        }

        if ($attribute === 'tax') {
            return $this->tax();
        }

        if ($attribute === 'subtotal') {
            return $this->subtotal();
        }

        return null;
    }

    /**
     * Get the carts content, if there is no cart content set yet, return a new empty Collection.
     *
     * @return array|Collection
     */
    protected function getContent()
    {
        return $this->session->has($this->instance)
            ? $this->session->get($this->instance)
            : new Collection();
    }

    /**
     * Create a new CartItem from the supplied attributes.
     *
     * @param mixed     $id
     * @param mixed     $name
     * @param float|int $qty
     * @param float     $price
     * @param float     $taxrate
     *
     * @return CartItem
     */
    private function createCartItem($id, $name, $qty, $price, array $options, $taxrate)
    {
        if ($id instanceof Buyable) {
            $cartItem = CartItem::fromBuyable($id, $qty ?: []);
            $cartItem->setQuantity($name ?: 1);
            $cartItem->associate($id);
        } elseif (is_array($id)) {
            $cartItem = CartItem::fromArray($id);
            $cartItem->setQuantity($id['qty']);
        } else {
            $cartItem = CartItem::fromAttributes($id, $name, $price, $options);
            $cartItem->setQuantity($qty);
        }

        if (isset($taxrate) && is_numeric($taxrate)) {
            $cartItem->setTaxRate($taxrate);
        } else {
            $cartItem->setTaxRate(config('Cart')->tax);
        }

        return $cartItem;
    }

    /**
     * Check if the item is a multidimensional array or an array of Buyables.
     *
     * @param mixed $item
     */
    private function isMulti($item): bool
    {
        if (! is_array($item)) {
            return false;
        }

        return is_array(reset($item)) || reset($item) instanceof Buyable;
    }

    /**
     * @param mixed $identifier
     *
     * @return bool
     */
    protected function storedCartWithIdentifierExists($identifier)
    {
        return $this->model->where('identifier', $identifier)->where('instance', $this->currentInstance())->first()
            ? true
            : false;
    }
}
