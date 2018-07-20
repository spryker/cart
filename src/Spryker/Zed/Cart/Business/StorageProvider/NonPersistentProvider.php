<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Cart\Business\StorageProvider;

use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\Cart\Business\Exception\InvalidQuantityExeption;
use Traversable;

class NonPersistentProvider implements StorageProviderInterface
{
    /**
     * @var \Spryker\Zed\CartExtension\Dependency\Plugin\CartItemOperationStrategyInterface[]
     */
    protected $cartAddItemStrategies;

    /**
     * @var \Spryker\Zed\CartExtension\Dependency\Plugin\CartItemOperationStrategyInterface[]
     */
    protected $cartRemoveItemStrategies;

    /**
     * @param \Spryker\Zed\CartExtension\Dependency\Plugin\CartItemOperationStrategyInterface[] $cartAddItemStrategies
     * @param \Spryker\Zed\CartExtension\Dependency\Plugin\CartItemOperationStrategyInterface[] $cartRemoveItemStrategies
     */
    public function __construct(
        array $cartAddItemStrategies,
        array $cartRemoveItemStrategies
    ) {
        $this->cartAddItemStrategies = $cartAddItemStrategies;
        $this->cartRemoveItemStrategies = $cartRemoveItemStrategies;
    }

    /**
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function addItems(CartChangeTransfer $cartChangeTransfer)
    {
        foreach ($cartChangeTransfer->getItems() as $itemTransfer) {
            $this->addItem($itemTransfer, $cartChangeTransfer->getQuote());
        }

        return $cartChangeTransfer->getQuote();
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return void
     */
    protected function addItem(ItemTransfer $itemTransfer, QuoteTransfer $quoteTransfer)
    {
        foreach ($this->cartAddItemStrategies as $cartAddItemStrategy) {
            if ($cartAddItemStrategy->isApplicaple($itemTransfer, $quoteTransfer)) {
                $cartAddItemStrategy->excute($itemTransfer, $quoteTransfer);
                return;
            }
        }

        $this->isValidQuantity($itemTransfer);
        $cartIndex = $this->createCartIndex($quoteTransfer->getItems());

        $itemIdentifier = $this->getItemIdentifier($itemTransfer);
        if (isset($cartIndex[$itemIdentifier])) {
            $this->increaseExistingItem($quoteTransfer->getItems(), $cartIndex[$itemIdentifier], $itemTransfer);
        } else {
            $quoteTransfer->getItems()->append($itemTransfer);
        }
    }

    /**
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function removeItems(CartChangeTransfer $cartChangeTransfer)
    {
        foreach ($cartChangeTransfer->getItems() as $itemTransfer) {
            $this->removeItem($itemTransfer, $cartChangeTransfer->getQuote());
        }

        return $cartChangeTransfer->getQuote();
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return void
     */
    protected function removeItem(ItemTransfer $itemTransfer, QuoteTransfer $quoteTransfer)
    {
        foreach ($this->cartRemoveItemStrategies as $cartRemoveItemStrategy) {
            if ($cartRemoveItemStrategy->isApplicaple($itemTransfer, $quoteTransfer)) {
                $cartRemoveItemStrategy->excute($itemTransfer, $quoteTransfer);
                return;
            }
        }
        $this->isValidQuantity($itemTransfer);
        $cartIndex = $this->createCartIndex($quoteTransfer->getItems());

        $itemIdentifier = $this->getItemIdentifier($itemTransfer);
        if (isset($cartIndex[$itemIdentifier])) {
            $this->decreaseExistingItem($quoteTransfer->getItems(), $itemIdentifier, $itemTransfer, $cartIndex);
        }
    }

    /**
     * @param \Traversable|\Generated\Shared\Transfer\ItemTransfer[] $cartItems
     *
     * @return array
     */
    protected function createCartIndex(Traversable $cartItems)
    {
        $cartIndex = [];
        foreach ($cartItems as $key => $itemTransfer) {
            $itemIdentifier = $this->getItemIdentifier($itemTransfer);
            $cartIndex[$itemIdentifier] = $key;
        }

        return $cartIndex;
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     *
     * @return string
     */
    protected function getItemIdentifier(ItemTransfer $itemTransfer)
    {
        return $itemTransfer->getGroupKey() ? $itemTransfer->getGroupKey() : $itemTransfer->getSku();
    }

    /**
     * @param \Traversable|\Generated\Shared\Transfer\ItemTransfer[] $existingItems
     * @param string $itemIdentifier
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     * @param array $cartIndex
     *
     * @return void
     */
    protected function decreaseExistingItem(Traversable $existingItems, $itemIdentifier, $itemTransfer, array $cartIndex)
    {
        $existingItemTransfer = null;
        $itemIndex = null;
        foreach ($existingItems as $index => $currentItemTransfer) {
            if ($currentItemTransfer->getGroupKey() === $itemIdentifier) {
                $existingItemTransfer = $currentItemTransfer;
                $itemIndex = $index;
                break;
            }
        }

        if ($existingItemTransfer === null) {
            $itemIndex = $cartIndex[$itemIdentifier];
            $existingItemTransfer = $existingItems[$itemIndex];
        }

        $changedQuantity = $existingItemTransfer->getQuantity() - $itemTransfer->getQuantity();

        if ($changedQuantity > 0) {
            $existingItemTransfer->setQuantity($changedQuantity);
        } else {
            unset($existingItems[$itemIndex]);
        }
    }

    /**
     * @param \Traversable|\Generated\Shared\Transfer\ItemTransfer[] $existingItems
     * @param int $index
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     *
     * @return void
     */
    protected function increaseExistingItem(Traversable $existingItems, $index, $itemTransfer)
    {
        $existingItemTransfer = $existingItems[$index];
        $changedQuantity = $existingItemTransfer->getQuantity() + $itemTransfer->getQuantity();

        $existingItemTransfer->setQuantity($changedQuantity);
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     *
     * @throws \Spryker\Zed\Cart\Business\Exception\InvalidQuantityExeption
     *
     * @return bool
     */
    protected function isValidQuantity(ItemTransfer $itemTransfer)
    {
        if ($itemTransfer->getQuantity() < 1) {
            throw new InvalidQuantityExeption(
                sprintf(
                    'Could not change cart item "%d" with "%d" as value.',
                    $itemTransfer->getSku(),
                    $itemTransfer->getQuantity()
                )
            );
        }

        return true;
    }
}
