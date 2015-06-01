<?php

namespace Unit\SprykerFeature\Zed\Cart\Business\StorageProvider;

use Generated\Shared\Cart\CartInterface;
use Generated\Shared\Cart\CartItemInterface;
use Generated\Shared\Transfer\CartItemsTransfer;
use Generated\Shared\Transfer\CartItemTransfer;
use SprykerEngine\Shared\Transfer\AbstractTransfer;
use Generated\Shared\Transfer\CartTransfer;
use SprykerFeature\Zed\Cart\Business\StorageProvider\InMemoryProvider;
use SprykerFeature\Zed\Cart\Business\StorageProvider\StorageProviderInterface;

/**
 * @group Cart
 * @group Zed
 * @group Business
 * @group InMemoryProviderTest
 */
class InMemoryProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StorageProviderInterface
     */
    private $provider;

    protected function setUp()
    {
        // $this->markTestSkipped();
        parent::setUp();
        $this->provider = new InMemoryProvider();
    }

    //@todo test with more then 1 item

    public function testAddExistingItem()
    {
        $cart = $this->createCartWithItem($itemId = '123', $existingQuantity = 1);

        $newItem = $this->createItem($itemId, $newQuantity = 3);
        $newItems = new CartItemsTransfer();
        $newItems->addCartItem($newItem);

        $changedCart = $this->provider->addItems($cart, $newItems);
        $changedItems = $changedCart->getItems();
        $this->assertCount(1, $changedItems);

        /** @var Item $changedItem */
        //$changedItem = $changedItems->getFirstItem();
        $changedItem = $changedItems[0];
        $this->assertEquals($itemId, $changedItem->getId());
        $this->assertEquals(
            $existingQuantity + $newQuantity,
            $changedItem->getQuantity()
        );
    }

    /**
     * @group Cart
     */
    public function testAddNewItem()
    {
        $cart = $this->createCartWithItem($itemId = '123', $existingQuantity = 1);

        $newItem = $this->createItem($newId = '321', $newQuantity = 3);
        $newItems = new CartItemsTransfer();
        $newItems->addCartItem($newItem);

        $changedCart = $this->provider->addItems($cart, $newItems);
        $changedItems = $changedCart->getItems();
        $this->assertCount(2, $changedItems);

        $this->assertTrue($changedItems->offsetExists($itemId));
        $this->assertTrue($changedItems->offsetExists($newId));

        /** @var CartItemInterface $addedItem */
        $addedItem = $changedItems->offsetGet($newId);
        $this->assertEquals($newId, $addedItem->getId());
        $this->assertEquals($newQuantity, $addedItem->getQuantity());

        /** @var CartItemInterface $existingItem */
        $existingItem = $changedItems->offsetGet($itemId);
        $this->assertEquals($itemId, $existingItem->getId());
        $this->assertEquals($existingQuantity, $existingItem->getQuantity());
    }

    /**
     * @group Cart
     */
    public function testRemoveExistingItem()
    {
        $cart = $this->createCartWithItem($itemId = '123', $existingQuantity = 1);
        $newItem = $this->createItem($itemId, $reduceQuantity = 1);
        $newItems = new CartItemsTransfer();
        $newItems->addCartItem($newItem);

        $changedCart = $this->provider->removeItems($cart, $newItems);
        $this->assertCount(0, $changedCart->getItems());
    }

    /**
     * @group Cart
     */
    public function testRemoveNotExistingItem()
    {
        $cart = $this->createCartWithItem($itemId = '123', $existingQuantity = 1);
        $newItem = $this->createItem($deleteItemId = '321', $reduceQuantity = 1);
        $newItems = new CartItemsTransfer();
        $newItems->addCartItem($newItem);

        $changedCart = $this->provider->removeItems($cart, $newItems);
        $changedItems = $changedCart->getItems();
        $this->assertCount(1, $changedItems);
        /** @var CartItemInterface $item */
        // $item = $changedItems->getFirstItem();
        $item = $changedItems[0];
        $this->assertEquals($itemId, $item->getId());
        $this->assertEquals($existingQuantity, $item->getQuantity());
    }

    /**
     * @group Cart
     */
    public function testReduceWithMoreThenExists()
    {
        $cart = $this->createCartWithItem($itemId = '123', $existingQuantity = 1);
        $newItem = $this->createItem($itemId, $reduceQuantity = 3);
        $newItems = new CartItemsTransfer();
        $newItems->addCartItem($newItem);

        $changedCart = $this->provider->removeItems($cart, $newItems);
        $this->assertCount(0, $changedCart->getItems());
    }

    /**
     * @group Cart
     * @expectedException \SprykerFeature\Zed\Cart\Business\Exception\InvalidArgumentException
     * @expectedExceptionMessage Could not increase cart item 123 with -3 as value
     */
    public function testIncreaseWithNegativeValue()
    {
        $cart = $this->createCartWithItem($itemId = '123', $existingQuantity = 1);

        $newItem = $this->createItem($newId = '123', $newQuantity = -3);
        $newItems = new CartItemsTransfer();
        $newItems->addCartItem($newItem);

        $this->provider->addItems($cart, $newItems);
    }

    /**
     * @group Cart
     * @expectedException \SprykerFeature\Zed\Cart\Business\Exception\InvalidArgumentException
     * @expectedExceptionMessage Could not increase cart item 123 with 0 as value
     */
    public function testIncreaseWithZeroValue()
    {
        $cart = $this->createCartWithItem($itemId = '123', $existingQuantity = 1);

        $newItem = $this->createItem($newId = '123', $newQuantity = 0);
        $newItems = new CartItemsTransfer();
        $newItems->addCartItem($newItem);

        $this->provider->addItems($cart, $newItems);
    }

    /**
     * @group Cart
     * @expectedException \SprykerFeature\Zed\Cart\Business\Exception\InvalidArgumentException
     * @expectedExceptionMessage Could not decrease cart item 123 with -3 as value
     */
    public function testDecreaseWithNegativeValue()
    {
        $cart = $this->createCartWithItem($itemId = '123', $existingQuantity = 1);

        $newItem = $this->createItem($newId = '123', $newQuantity = -3);
        $newItems = new CartItemsTransfer();
        $newItems->addCartItem($newItem);

        $this->provider->removeItems($cart, $newItems);
    }

    /**
     * @group Cart
     * @expectedException \SprykerFeature\Zed\Cart\Business\Exception\InvalidArgumentException
     * @expectedExceptionMessage Could not decrease cart item 123 with 0 as value
     */
    public function testDecreaseWithZeroValue()
    {
        $cart = $this->createCartWithItem($itemId = '123', $existingQuantity = 1);

        $newItem = $this->createItem($newId = '123', $newQuantity = 0);
        $newItems = new CartItemsTransfer();
        $newItems->addCartItem($newItem);

        $this->provider->removeItems($cart, $newItems);
    }


    /**
     *
     * @param string $itemId
     * @param int $itemQuantity
     *
     * @return CartInterface
     */
    protected function createCartWithItem($itemId, $itemQuantity)
    {
        $cart = new CartTransfer();
        $existingItem = $this->createItem($itemId, $itemQuantity);
        $cart->addItem($existingItem);

        return $cart;
    }

    /**
     * @param string $itemId
     * @param int $itemQuantity
     *
     * @return CartItemInterface|AbstractTransfer
     */
    protected function createItem($itemId, $itemQuantity)
    {
        $existingItem = new CartItemTransfer();
        $existingItem->setId($itemId);
        $existingItem->setQuantity($itemQuantity);

        return $existingItem;
    }
}