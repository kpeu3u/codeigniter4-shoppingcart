<?php

declare(strict_types=1);

namespace Tests;

use CodeIgniter\Events\Events;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Exception;
use ShoppingCart\Cart;
use ShoppingCart\CartItem;
use Tests\Support\Database\Seeds\CartSeeder;
use Tightenco\Collect\Support\Collection;

/**
 * @internal
 */
final class CartTest extends CIUnitTestCase
{
    use CartAssertions;
    use DatabaseTestTrait;

    protected Cart $getCart;
    protected $seed = CartSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        Events::on('cart.added', static function ($arg): void {});
        Events::on('cart.updated', static function ($arg): void {});
        Events::on('cart.removed', static function ($arg): void {});
        Events::on('cart.stored', static function (): void {});
        Events::on('cart.restored', static function (): void {});

        $this->getCart = new Cart();
    }

    public function testItHasADefaultInstance(): void
    {
        $this->assertSame(Cart::DEFAULT_INSTANCE, $this->getCart->currentInstance());
    }

    public function testItCanHaveMultipleInstances(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyAbleProduct(1, 'First item'));
        $cart->instance('wishlist')->add(new BuyAbleProduct(2, 'Second item'));

        $this->assertItemsInCart(1, $cart->instance(Cart::DEFAULT_INSTANCE));
        $this->assertItemsInCart(1, $cart->instance('wishlist'));
    }

    public function testItCanAddAnItem(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyAbleProduct());

        $this->assertSame(1, $cart->count());
        $this->assertEventTriggered('cart.added');
    }

    /**
     * @throws Exception
     */
    public function testItWillReturnTheCartitemOfTheAddedItem(): void
    {
        $cart = $this->getCart;

        $cartItem = $cart->add(new BuyAbleProduct());

        $this->assertInstanceOf(CartItem::class, $cartItem);
        $this->assertSame('027c91341fd5cf4d2579b49c4b6a90da', $cartItem->rowId);
        $this->assertEventTriggered('cart.added');
    }

    /**
     * @throws Exception
     */
    public function testItCanAddMultipleBuyableItemsAtOnce(): void
    {
        $cart = $this->getCart;

        $cart->add([new BuyableProduct(1), new BuyableProduct(2)]);

        $this->assertSame(2, $cart->count());
        $this->assertEventTriggered('cart.added');
    }

    /**
     * @throws Exception
     */
    public function testItWillReturnAnArrayOfCartitemsWhenYouAddMultipleItemsAtOnce(): void
    {
        $cart = $this->getCart;

        $cartItems = $cart->add([new BuyableProduct(1), new BuyableProduct(2)]);

        $this->assertIsArray($cartItems);
        $this->assertCount(2, $cartItems);
        $this->assertContainsOnlyInstancesOf(CartItem::class, $cartItems);
        $this->assertEventTriggered('cart.added');
    }

    /**
     * @throws Exception
     */
    public function testItCanAddAnItemFromAttributes(): void
    {
        $cart = $this->getCart;

        $cart->add(1, 'Test item', 1, 10.00);

        $this->assertSame(1, $cart->count());

        $this->assertEventTriggered('cart.added');
    }

    /**
     * @throws Exception
     */
    public function testItCanAddAnItemFromAnArray(): void
    {
        $cart = $this->getCart;

        $cart->add(['id' => 1, 'name' => 'Test item', 'qty' => 1, 'price' => 10.00]);

        $this->assertSame(1, $cart->count());

        $this->assertEventTriggered('cart.added');
    }

    /**
     * @throws Exception
     */
    public function testItCanAddMultipleArrayItemsAtOnce(): void
    {
        $cart = $this->getCart;

        $cart->add([
            ['id' => 1, 'name' => 'Test item 1', 'qty' => 1, 'price' => 10.00],
            ['id' => 2, 'name' => 'Test item 2', 'qty' => 1, 'price' => 10.00],
        ]);

        $this->assertSame(2, $cart->count());

        $this->assertEventTriggered('cart.added');
    }

    /**
     * @throws Exception
     */
    public function testItCanAddAnItemWithOptions(): void
    {
        $cart = $this->getCart;

        $options = ['size' => 'XL', 'color' => 'red'];

        $cart->add(new BuyableProduct(), 1, $options);

        $cartItem = $cart->get('07d5da5550494c62daf9993cf954303f');

        $this->assertInstanceOf(CartItem::class, $cartItem);
        $this->assertSame('XL', $cartItem->options->size);
        $this->assertSame('red', $cartItem->options->color);

        $this->assertEventTriggered('cart.added');
    }

    public function testItWillValidateTheIdentifier(): void
    {
        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessage('Please supply a valid identifier');

        $cart = $this->getCart;

        $cart->add(null, 'Some title', 1, 10.00);
    }

    public function testItWillValidateTheName(): void
    {
        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessage('Please supply a valid name');

        $cart = $this->getCart;

        $cart->add(1, null, 1, 10.00);
    }

    public function testItWillValidateTheQuantity(): void
    {
        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessage('Please supply a valid quantity');

        $cart = $this->getCart;

        $cart->add(1, 'Some title', 'invalid', 10.00);
    }

    public function testItWillValidateThePrice(): void
    {
        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessage('Please supply a valid price');

        $cart = $this->getCart;

        $cart->add(1, 'Some title', 1, 'invalid');
    }

    public function testItWillUpdateTheCartIfTheItemAlreadyExistsInTheCart(): void
    {
        $cart = $this->getCart;

        $item = new BuyableProduct();

        $cart->add($item);
        $cart->add($item);

        $this->assertItemsInCart(2, $cart);
        $this->assertRowsInCart(1, $cart);
    }

    public function testItWillKeepUpdatingTheQuantityWhenAnItemIsAddedMultipleTimes(): void
    {
        $cart = $this->getCart;

        $item = new BuyableProduct();

        $cart->add($item);
        $cart->add($item);
        $cart->add($item);

        $this->assertItemsInCart(3, $cart);
        $this->assertRowsInCart(1, $cart);
    }

    /**
     * @throws Exception
     */
    public function testItCanUpdateTheQuantityOfAnExistingItemInTheCart(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct());

        $cart->update('027c91341fd5cf4d2579b49c4b6a90da', 2);

        $this->assertItemsInCart(2, $cart);
        $this->assertRowsInCart(1, $cart);

        $this->assertEventTriggered('cart.updated');
    }

    /**
     * @throws Exception
     */
    public function testItCanUpdateAnExistingItemInTheCartFromABuyable(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct());

        $cart->update('027c91341fd5cf4d2579b49c4b6a90da', new BuyableProduct(1, 'Different description'));

        $this->assertItemsInCart(1, $cart);
        $this->assertSame('Different description', $cart->get('027c91341fd5cf4d2579b49c4b6a90da')->name);

        $this->assertEventTriggered('cart.updated');
    }

    /**
     * @throws Exception
     */
    public function testItCanUpdateAnExistingItemInTheCartFromAnArray(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct());

        $cart->update('027c91341fd5cf4d2579b49c4b6a90da', ['name' => 'Different description']);

        $this->assertItemsInCart(1, $cart);
        $this->assertSame('Different description', $cart->get('027c91341fd5cf4d2579b49c4b6a90da')->name);

        $this->assertEventTriggered('cart.updated');
    }

    public function testItWillThrowAnExceptionIfARowidWasNotFound(): void
    {
        $this->expectException('\ShoppingCart\Exceptions\InvalidRowIDException');

        $cart = $this->getCart;

        $cart->add(new BuyableProduct());

        $cart->update('none-existing-rowid', new BuyableProduct(1, 'Different description'));
    }

    public function testItWillRegenerateTheRowidIfTheOptionsChanged(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(), 1, ['color' => 'red']);

        $cart->update('ea65e0bdcd1967c4b3149e9e780177c0', ['options' => ['color' => 'blue']]);

        $this->assertItemsInCart(1, $cart);
        $this->assertSame('7e70a1e9aaadd18c72921a07aae5d011', $cart->content()->first()->rowId);
        $this->assertSame('blue', $cart->get('7e70a1e9aaadd18c72921a07aae5d011')->options->color);
    }

    public function testItWillAddTheItemToAnExistingRowIfTheOptionsChangedToAnExistingRowid(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(), 1, ['color' => 'red']);
        $cart->add(new BuyableProduct(), 1, ['color' => 'blue']);

        $cart->update('7e70a1e9aaadd18c72921a07aae5d011', ['options' => ['color' => 'red']]);

        $this->assertItemsInCart(2, $cart);
        $this->assertRowsInCart(1, $cart);
    }

    /**
     * @throws Exception
     */
    public function testItCanRemoveAnItemFromTheCart(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct());

        $cart->remove('027c91341fd5cf4d2579b49c4b6a90da');

        $this->assertItemsInCart(0, $cart);
        $this->assertRowsInCart(0, $cart);

        $this->assertEventTriggered('cart.removed');
    }

    /**
     * @throws Exception
     */
    public function testItWillRemoveTheItemIfItsQuantityWasSetToZero(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct());

        $cart->update('027c91341fd5cf4d2579b49c4b6a90da', 0);

        $this->assertItemsInCart(0, $cart);
        $this->assertRowsInCart(0, $cart);

        $this->assertEventTriggered('cart.removed');
    }

    /**
     * @throws Exception
     */
    public function testItWillRemoveTheItemIfItsQuantityWasSetNegative(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct());

        $cart->update('027c91341fd5cf4d2579b49c4b6a90da', -1);

        $this->assertItemsInCart(0, $cart);
        $this->assertRowsInCart(0, $cart);

        $this->assertEventTriggered('cart.removed');
    }

    public function testItCanGetAnItemFromTheCartByItsRowid(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct());

        $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

        $this->assertInstanceOf(CartItem::class, $cartItem);
    }

    public function testItCanGetTheContentOfTheCart(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1));
        $cart->add(new BuyableProduct(2));

        $content = $cart->content();

        $this->assertInstanceOf(Collection::class, $content);
        $this->assertCount(2, $content);
    }

    public function testItWillReturnAnEmptyCollectionIfTheCartIsEmpty(): void
    {
        $cart = $this->getCart;

        $content = $cart->content();

        $this->assertInstanceOf(Collection::class, $content);
        $this->assertCount(0, $content);
    }

    public function testItWillIncludeTheTaxAndSubtotalWhenConvertedToAnArray(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1));
        $cart->add(new BuyableProduct(2));

        $content = $cart->content();

        $this->assertInstanceOf(Collection::class, $content);
    }

    public function testItCanDestroyACart(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct());

        $this->assertItemsInCart(1, $cart);

        $cart->destroy();

        $this->assertItemsInCart(0, $cart);
    }

    public function testItCanGetTheTotalPriceOfTheCartContent(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'First item', 10.00));
        $cart->add(new BuyableProduct(2, 'Second item', 25.00), 2);

        $this->assertItemsInCart(3, $cart);
        $this->assertSame('60.00', $cart->subtotal());
    }

    public function testItCanReturnAFormattedTotal(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'First item', 1000.00));
        $cart->add(new BuyableProduct(2, 'Second item', 2500.00), 2);

        $this->assertItemsInCart(3, $cart);
        $this->assertSame('6.000,00', $cart->subtotal(2, ',', '.'));
    }

    public function testItCanSearchTheCartForASpecificItem(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'Some item'));
        $cart->add(new BuyableProduct(2, 'Another item'));

        $cartItem = $cart->search(static fn ($cartItem, $rowId) => $cartItem->name === 'Some item');

        $this->assertInstanceOf(Collection::class, $cartItem);
        $this->assertCount(1, $cartItem);
        $this->assertInstanceOf(CartItem::class, $cartItem->first());
        $this->assertSame(1, $cartItem->first()->id);
    }

    public function testItCanSearchTheCartForMultipleItems(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'Some item'));
        $cart->add(new BuyableProduct(2, 'Some item'));
        $cart->add(new BuyableProduct(3, 'Another item'));

        $cartItem = $cart->search(static fn ($cartItem, $rowId) => $cartItem->name === 'Some item');

        $this->assertInstanceOf(Collection::class, $cartItem);
    }

    public function testItCanSearchTheCartForASpecificItemWithOptions(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'Some item'), 1, ['color' => 'red']);
        $cart->add(new BuyableProduct(2, 'Another item'), 1, ['color' => 'blue']);

        $cartItem = $cart->search(static fn ($cartItem, $rowId) => $cartItem->options->color === 'red');

        $this->assertInstanceOf(Collection::class, $cartItem);
        $this->assertCount(1, $cartItem);
        $this->assertInstanceOf(CartItem::class, $cartItem->first());
        $this->assertSame(1, $cartItem->first()->id);
    }

    public function testItCanAssociateTheCartItemWithAModel(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyAbleProduct());

        $cart->associate('027c91341fd5cf4d2579b49c4b6a90da', new ProductModel());

        $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

        $this->assertSame(ProductModel::class, $cartItem->associatedModel);
    }

    public function testItWillThrowAnExceptionWhenANonExistingModelIsBeingAssociated(): void
    {
        $this->expectException('\ShoppingCart\Exceptions\UnknownModelException');
        $this->expectExceptionMessage('The supplied model SomeModel does not exist.');

        $cart = $this->getCart;

        $cart->add(1, 'Test item', 1, 10.00);

        $cart->associate('027c91341fd5cf4d2579b49c4b6a90da', 'SomeModel');
    }

    public function testItCanGetTheAssociatedModelOfACartItem(): void
    {
        $cart = $this->getCart;

        $cart->add(1, 'Test item', 1, 10.00);

        $cart->associate('027c91341fd5cf4d2579b49c4b6a90da', new ProductModel());

        $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

        $this->assertInstanceOf(ProductModel::class, $cartItem->model);
        $this->assertSame('Some value', $cartItem->model->someValue);
    }

    public function testItCanCalculateTheSubtotalOfACartItem(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'Some title', 9.99), 3);

        $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

        $this->assertSame('29.97', $cartItem->subtotal);
    }

    public function testItCanCalculateTaxBasedOnTheDefaultTaxRateInTheConfig(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'Some title', 10.00), 1);

        $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

        $this->assertSame('2.10', $cartItem->tax());
    }

    public function testItCanCalculateTaxBasedOnTheSpecifiedTax(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'Some title', 10.00), 1);

        $cart->setTax('027c91341fd5cf4d2579b49c4b6a90da', 19);

        $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

        $this->assertSame('1.90', $cartItem->tax());
    }

    public function testItCanReturnTheCalculatedTaxFormatted(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'Some title', 10000.00), 1);

        $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

        $this->assertSame('2.100,00', $cartItem->tax(2, ',', '.'));
    }

    public function testItCanReturnFormattedTotalTax(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'Some title', 1000.00), 1);
        $cart->add(new BuyableProduct(2, 'Some title', 2000.00), 2);

        $this->assertSame('1.050,00', $cart->tax(2, ',', '.'));
    }

    public function testItCanReturnTheSubtotal(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'Some title', 10.00), 1);
        $cart->add(new BuyableProduct(2, 'Some title', 20.00), 2);

        $this->assertSame('50.00', $cart->subtotal());
    }

    public function testItCanReturnFormattedSubtotal(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'Some title', 1000.00), 1);
        $cart->add(new BuyableProduct(2, 'Some title', 2000.00), 2);

        $this->assertSame('5000,00', $cart->subtotal(2, ',', ''));
    }

    /**
     * @throws Exception
     */
    public function testItCanStoreTheCartInADatabase(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct());

        $cart->store($identifier = 123);

        $serialized = serialize($cart->content());

        $this->seeInDatabase('shoppingcart', ['identifier' => $identifier, 'instance' => 'default', 'content' => $serialized]);
    }

    public function testItCanCalculateAllValues(): void
    {
        $cart = $this->getCart;

        $cart->add(new BuyableProduct(1, 'First item', 10.00), 2);

        $cartItem = $cart->get('027c91341fd5cf4d2579b49c4b6a90da');

        $cart->setTax('027c91341fd5cf4d2579b49c4b6a90da', 19);

        $this->assertSame('10.00', $cartItem->price(2));
        $this->assertSame('11.90', $cartItem->priceTax(2));
        $this->assertSame('23.80', $cartItem->total(2));
        $this->assertSame('1.90', $cartItem->tax(2));
        $this->assertSame('3.80', $cartItem->taxTotal(2));

        $this->assertSame('20.00', $cart->subtotal(2));
        $this->assertSame('23.80', $cart->total(2));
        $this->assertSame('3.80', $cart->tax(2));
    }

    /**
     * Set the config number format.
     */
    private function setConfigFormat(int $decimals, string $decimalPoint, string $thousandSeparator): ?object
    {
        $config = config('Cart');

        $config->format['decimals']           = $decimals;
        $config->format['decimal_point']      = $decimalPoint;
        $config->format['thousand_separator'] = $thousandSeparator;

        return $config;
    }
}
