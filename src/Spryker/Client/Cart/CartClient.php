<?php

/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace Spryker\Client\Cart;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\CartTransfer;
use Generated\Shared\Transfer\ChangeTransfer;
use Spryker\Client\Kernel\AbstractClient;
use Spryker\Client\Cart\Session\CartSessionInterface;
use Spryker\Client\Cart\Zed\CartStubInterface;

/**
 * @method CartDependencyContainer getDependencyContainer()
 */
class CartClient extends AbstractClient implements CartClientInterface
{

    /**
     * @return CartTransfer|CartTransfer
     */
    public function getCart()
    {
        return $this->getSession()->getCart();
    }

    /**
     * @return CartSessionInterface
     */
    protected function getSession()
    {
        return $this->getDependencyContainer()->createSession();
    }

    /**
     * @return CartTransfer
     */
    public function clearCart()
    {
        $cartTransfer = new CartTransfer();

        $this->getSession()
            ->setItemCount(0)
            ->setCart($cartTransfer);

        return $cartTransfer;
    }

    /**
     * @return int
     */
    public function getItemCount()
    {
        return $this->getSession()->getItemCount();
    }

    /**
     * @param ItemTransfer $itemTransfer
     *
     * @return CartTransfer
     */
    public function addItem(ItemTransfer $itemTransfer)
    {
        $changeTransfer = $this->prepareCartChange($itemTransfer);
        $cartTransfer = $this->getZedStub()->addItem($changeTransfer);

        return $this->handleCartResponse($cartTransfer);
    }

    /**
     * @return CartStubInterface
     */
    protected function getZedStub()
    {
        return $this->getDependencyContainer()->createZedStub();
    }

    /**
     * @param ItemTransfer $itemTransfer
     *
     * @return CartTransfer
     */
    public function removeItem(ItemTransfer $itemTransfer)
    {
        $itemTransfer = $this->mergeCartItems(
            $itemTransfer,
            $this->findItem($itemTransfer)
        );

        $changeTransfer = $this->prepareCartChange($itemTransfer);
        $cartTransfer = $this->getZedStub()->removeItem($changeTransfer);

        return $this->handleCartResponse($cartTransfer);
    }

    /**
     * @param ItemTransfer $itemToFind
     *
     * @return ItemTransfer
     */
    protected function findItem(ItemTransfer $itemToFind)
    {
        $cartTransfer = $this->getCart();

        foreach ($cartTransfer->getItems() as $itemTransfer) {
            if ($itemTransfer->getSku() === $itemToFind->getSku()) {
                $matchingItemTransfer = clone $itemTransfer;  //@todo is clone still needed?
                return $matchingItemTransfer;
            }
        }

        throw new \InvalidArgumentException(
            sprintf('No item with sku "%s" found in cart.', $itemToFind->getSku())
        );
    }

    /**
     * @param ItemTransfer $itemTransfer
     * @param int $quantity
     *
     * @return CartTransfer
     */
    public function changeItemQuantity(ItemTransfer $itemTransfer, $quantity = 1)
    {
        if ($quantity === 0) {
            return $this->removeItem($itemTransfer);
        }

        $itemTransfer = $this->findItem($itemTransfer);
        $delta = abs($itemTransfer->getQuantity() - $quantity);

        if ($delta === 0) {
            return $this->getCart();
        }

        if ($itemTransfer->getQuantity() > $quantity) {
            return $this->decreaseItemQuantity($itemTransfer, $delta);
        }

        return $this->increaseItemQuantity($itemTransfer, $delta);
    }

    /**
     * @param ItemTransfer $itemTransfer
     * @param int $quantity
     *
     * @return CartTransfer
     */
    public function decreaseItemQuantity(ItemTransfer $itemTransfer, $quantity = 1)
    {
        $changeTransfer = $this->createChangeTransferWithAdjustedQuantity($itemTransfer, $quantity);

        $cartTransfer = $this->getZedStub()->decreaseItemQuantity($changeTransfer);

        return $this->handleCartResponse($cartTransfer);
    }

    /**
     * @param ItemTransfer $itemTransfer
     * @param int $quantity
     *
     * @return CartTransfer
     */
    public function increaseItemQuantity(ItemTransfer $itemTransfer, $quantity = 1)
    {
        $changeTransfer = $this->createChangeTransferWithAdjustedQuantity($itemTransfer, $quantity);

        $cartTransfer = $this->getZedStub()->increaseItemQuantity($changeTransfer);

        return $this->handleCartResponse($cartTransfer);
    }

    /**
     * @return CartTransfer
     */
    public function recalculate()
    {
        $cartTransfer = $this->getCart();
        $cartTransfer = $this->getZedStub()->recalculate($cartTransfer);

        return $this->handleCartResponse($cartTransfer);
    }

    /**
     * @return ChangeTransfer
     */
    protected function createCartChange()
    {
        $cartTransfer = $this->getCart();
        $changeTransfer = new ChangeTransfer();
        $changeTransfer->setCart($cartTransfer);

        return $changeTransfer;
    }

    /**
     * @param ItemTransfer $itemTransfer
     *
     * @return ChangeTransfer
     */
    protected function prepareCartChange(ItemTransfer $itemTransfer)
    {
        $changeTransfer = $this->createCartChange();
        $changeTransfer->addItem($itemTransfer);

        return $changeTransfer;
    }

    /**
     * @param string $coupon
     *
     * @return CartTransfer
     */
    public function addCoupon($coupon)
    {
        $changeTransfer = $this->createCartChange();
        $changeTransfer->setCouponCode($coupon);

        $cartTransfer = $this->getZedStub()->addCoupon($changeTransfer);

        return $this->handleCartResponse($cartTransfer);
    }

    /**
     * @param string $coupon
     *
     * @return CartTransfer
     */
    public function removeCoupon($coupon)
    {
        $changeTransfer = $this->createCartChange();
        $changeTransfer->setCouponCode($coupon);

        $cartTransfer = $this->getZedStub()->removeCoupon($changeTransfer);

        return $this->handleCartResponse($cartTransfer);
    }

    /**
     * @return CartTransfer
     */
    public function clearCoupons()
    {
        $changeTransfer = $this->createCartChange();
        $cartTransfer = $this->getZedStub()->clearCoupons($changeTransfer);

        return $this->handleCartResponse($cartTransfer);
    }

    /**
     * @param ItemTransfer $itemTransfer
     * @param int $quantity
     *
     * @return ChangeTransfer
     */
    protected function createChangeTransferWithAdjustedQuantity(ItemTransfer $itemTransfer, $quantity)
    {
        $itemTransfer = $this->mergeCartItems(
            $itemTransfer,
            $this->findItem($itemTransfer)
        );

        $itemTransfer->setQuantity($quantity);

        return $this->prepareCartChange($itemTransfer);
    }

    /**
     * @param CartTransfer $cartTransfer
     *
     * @return CartTransfer
     */
    protected function handleCartResponse(CartTransfer $cartTransfer)
    {
        $this->getSession()->setCart($cartTransfer);

        return $cartTransfer;
    }

    /**
     * @param ItemTransfer $newItemTransfer
     * @param ItemTransfer $oldItemByIdentifier
     *
     * @return ItemTransfer
     */
    protected function mergeCartItems(ItemTransfer $newItemTransfer, ItemTransfer $oldItemByIdentifier)
    {
        $newItemTransfer->fromArray($oldItemByIdentifier->toArray(), true);

        return $newItemTransfer;
    }

}