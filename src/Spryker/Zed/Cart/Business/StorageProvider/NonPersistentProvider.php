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
     * @var array<\Spryker\Zed\CartExtension\Dependency\Plugin\CartOperationStrategyPluginInterface>
     */
    protected $cartAddItemStrategyPlugins;

    /**
     * @var array<\Spryker\Zed\CartExtension\Dependency\Plugin\CartOperationStrategyPluginInterface>
     */
    protected $cartRemoveItemStrategyPlugins;

    /**
     * @param array<\Spryker\Zed\CartExtension\Dependency\Plugin\CartOperationStrategyPluginInterface> $cartAddItemStrategyPlugins
     * @param array<\Spryker\Zed\CartExtension\Dependency\Plugin\CartOperationStrategyPluginInterface> $cartRemoveItemStrategyPlugins
     */
    public function __construct(
        array $cartAddItemStrategyPlugins,
        array $cartRemoveItemStrategyPlugins
    ) {
        $this->cartAddItemStrategyPlugins = $cartAddItemStrategyPlugins;
        $this->cartRemoveItemStrategyPlugins = $cartRemoveItemStrategyPlugins;
    }

    /**
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function addItems(CartChangeTransfer $cartChangeTransfer): QuoteTransfer
    {
        $quoteTransfer = $cartChangeTransfer->getQuoteOrFail();
        $cartIndex = $this->createCartIndex($quoteTransfer->getItems());

        foreach ($cartChangeTransfer->getItems() as $itemTransfer) {
            $cartIndex = $this->addItem($itemTransfer, $quoteTransfer, $cartIndex);
        }

        return $quoteTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     * @param array<string, int> $cartIndex
     *
     * @return array<string, int>
     */
    protected function addItem(ItemTransfer $itemTransfer, QuoteTransfer $quoteTransfer, array $cartIndex): array
    {
        $this->isValidQuantity($itemTransfer);
        foreach ($this->cartAddItemStrategyPlugins as $cartAddItemStrategyPlugin) {
            if ($cartAddItemStrategyPlugin->isApplicable($itemTransfer, $quoteTransfer)) {
                $cartAddItemStrategyPlugin->execute($itemTransfer, $quoteTransfer);

                return $cartIndex;
            }
        }

        $itemIdentifier = $this->getItemIdentifier($itemTransfer);
        if (isset($cartIndex[$itemIdentifier])) {
            $this->increaseExistingItem(
                $quoteTransfer->getItems()[$cartIndex[$itemIdentifier]],
                $itemTransfer,
            );

            return $cartIndex;
        }

        $quoteTransfer->getItems()->append($itemTransfer);
        $cartIndex[$itemIdentifier] = (int)array_key_last($quoteTransfer->getItems()->getArrayCopy());

        return $cartIndex;
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
    protected function removeItem(ItemTransfer $itemTransfer, QuoteTransfer $quoteTransfer): void
    {
        $this->isValidQuantity($itemTransfer);
        foreach ($this->cartRemoveItemStrategyPlugins as $cartRemoveItemStrategyPlugin) {
            if ($cartRemoveItemStrategyPlugin->isApplicable($itemTransfer, $quoteTransfer)) {
                $cartRemoveItemStrategyPlugin->execute($itemTransfer, $quoteTransfer);

                return;
            }
        }

        $cartIndex = $this->createCartIndex($quoteTransfer->getItems());
        $itemIdentifier = $this->getItemIdentifier($itemTransfer);
        if (isset($cartIndex[$itemIdentifier])) {
            $this->decreaseExistingItem(
                $quoteTransfer->getItems(),
                $itemTransfer,
                $cartIndex[$itemIdentifier],
            );
        }
    }

    /**
     * @param \Traversable<\Generated\Shared\Transfer\ItemTransfer> $cartItems
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
    protected function getItemIdentifier(ItemTransfer $itemTransfer): string
    {
        return $itemTransfer->getGroupKey() ?: $itemTransfer->getSku();
    }

    /**
     * @param \Traversable<int, \Generated\Shared\Transfer\ItemTransfer> $existingItems
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     * @param int $itemIndex
     *
     * @return void
     */
    protected function decreaseExistingItem(Traversable $existingItems, ItemTransfer $itemTransfer, int $itemIndex): void
    {
        /** @var \Generated\Shared\Transfer\ItemTransfer $existingItemTransfer */
        $existingItemTransfer = $existingItems[$itemIndex];
        $changedQuantity = $existingItemTransfer->getQuantity() - $itemTransfer->getQuantity();

        if ($changedQuantity > 0) {
            $existingItemTransfer->fromArray($itemTransfer->toArray());
            $existingItemTransfer->setQuantity($changedQuantity);

            return;
        }

        unset($existingItems[$itemIndex]);
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $existingItemTransfer
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     *
     * @return void
     */
    protected function increaseExistingItem(ItemTransfer $existingItemTransfer, ItemTransfer $itemTransfer)
    {
        $changesQuantity = $existingItemTransfer->getQuantity() + $itemTransfer->getQuantity();

        $existingItemTransfer->fromArray($itemTransfer->toArray());
        $existingItemTransfer->setQuantity($changesQuantity);
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
                    'Could not change the quantity of cart item "%s" to "%d".',
                    $itemTransfer->getSku(),
                    $itemTransfer->getQuantity(),
                ),
            );
        }

        return true;
    }
}
