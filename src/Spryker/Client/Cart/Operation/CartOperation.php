<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Cart\Operation;

use ArrayObject;
use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteResponseTransfer;
use Spryker\Client\Cart\CartChangeRequestExpander\CartChangeRequestExpanderInterface;
use Spryker\Client\Cart\Dependency\Client\CartToQuoteInterface;
use Spryker\Client\Cart\Zed\CartStubInterface;
use Spryker\Client\CartExtension\Dependency\Plugin\QuoteItemFinderPluginInterface;

class CartOperation implements CartOperationInterface
{
    /**
     * @var \Spryker\Client\Cart\Dependency\Client\CartToQuoteInterface
     */
    protected $quoteClient;

    /**
     * @var \Spryker\Client\Cart\Zed\CartStubInterface
     */
    protected $cartStub;

    /**
     * @var \Spryker\Client\Cart\CartChangeRequestExpander\CartChangeRequestExpanderInterface
     */
    protected $cartChangeRequestExpander;

    /**
     * @var \Spryker\Client\CartExtension\Dependency\Plugin\QuoteItemFinderPluginInterface
     */
    protected $quoteItemFinderPlugin;

    public function __construct(
        CartToQuoteInterface $quoteClient,
        CartStubInterface $cartStub,
        CartChangeRequestExpanderInterface $cartChangeRequestExpander,
        QuoteItemFinderPluginInterface $quoteItemFinderPlugin
    ) {
        $this->quoteClient = $quoteClient;
        $this->cartStub = $cartStub;
        $this->cartChangeRequestExpander = $cartChangeRequestExpander;
        $this->quoteItemFinderPlugin = $quoteItemFinderPlugin;
    }

    public function addToCart(CartChangeTransfer $cartChangeTransfer): QuoteResponseTransfer
    {
        $cartChangeTransfer->setQuote($this->quoteClient->getQuote());
        $cartChangeTransfer = $this->cartChangeRequestExpander->addItemsRequestExpand($cartChangeTransfer);

        $quoteResponseTransfer = $this->cartStub->addToCart($cartChangeTransfer);

        if ($quoteResponseTransfer->getIsSuccessful()) {
            $this->quoteClient->setQuote($quoteResponseTransfer->getQuoteTransfer());
        }

        return $quoteResponseTransfer;
    }

    public function removeFromCart(CartChangeTransfer $cartChangeTransfer): QuoteResponseTransfer
    {
        $cartChangeTransfer->setQuote($this->quoteClient->getQuote());
        $cartChangeTransfer = $this->cartChangeRequestExpander->removeItemRequestExpand($cartChangeTransfer);

        $quoteResponseTransfer = $this->cartStub->removeFromCart($cartChangeTransfer);

        if ($quoteResponseTransfer->getIsSuccessful()) {
            $this->quoteClient->setQuote($quoteResponseTransfer->getQuoteTransfer());
        }

        return $quoteResponseTransfer;
    }

    public function updateQuantity(CartChangeTransfer $cartChangeTransfer): QuoteResponseTransfer
    {
        $cartChangeTransferForAdding = $this->prepareCartChangeTransferForAdding($cartChangeTransfer);
        $cartChangeTransferForRemoval = $this->prepareCartChangeTransferForRemoval($cartChangeTransfer);

        $quoteResponseTransfer = $this->executeUpdateQuantity($cartChangeTransferForAdding, $cartChangeTransferForRemoval);

        if (!$quoteResponseTransfer->getIsSuccessful()) {
            return (new QuoteResponseTransfer())
                ->setQuoteTransfer($this->quoteClient->getQuote())
                ->setIsSuccessful(false);
        }

        $this->quoteClient->setQuote($quoteResponseTransfer->getQuoteTransfer());

        return $quoteResponseTransfer;
    }

    protected function executeUpdateQuantity(
        CartChangeTransfer $cartChangeTransferForAdding,
        CartChangeTransfer $cartChangeTransferForRemoval
    ): QuoteResponseTransfer {
        $quoteResponseTransfer = (new QuoteResponseTransfer())
            ->setQuoteTransfer($this->quoteClient->getQuote())
            ->setIsSuccessful(true);

        if (!$cartChangeTransferForAdding->getItems()->count() && !$cartChangeTransferForRemoval->getItems()->count()) {
            return $quoteResponseTransfer;
        }

        if ($cartChangeTransferForAdding->getItems()->count()) {
            $quoteResponseTransfer = $this->cartStub->addToCart($cartChangeTransferForAdding);
        }

        if ($quoteResponseTransfer->getIsSuccessful() && $cartChangeTransferForRemoval->getItems()->count()) {
            return $this->cartStub->removeFromCart($cartChangeTransferForRemoval);
        }

        return $quoteResponseTransfer;
    }

    protected function prepareCartChangeTransferForAdding(CartChangeTransfer $cartChangeTransfer): CartChangeTransfer
    {
        $cartChangeTransferForAdding = $this->createCartChangeTransfer();

        foreach ($cartChangeTransfer->getItems() as $changeItem) {
            $quoteItem = $this->findItem($changeItem->getSku(), $changeItem->getGroupKey());

            if (!$quoteItem || $changeItem->getQuantity() <= 0) {
                continue;
            }

            $delta = $changeItem->getQuantity() - $quoteItem->getQuantity();

            if ($delta <= 0) {
                continue;
            }

            $changeItemTransfer = clone $quoteItem;
            $changeItemTransfer->setQuantity($delta);

            $cartChangeTransferForAdding->addItem($changeItemTransfer);
        }

        if (!$cartChangeTransferForAdding->getItems()->count()) {
            return $cartChangeTransferForAdding;
        }

        return $this->cartChangeRequestExpander->addItemsRequestExpand($cartChangeTransferForAdding);
    }

    protected function prepareCartChangeTransferForRemoval(CartChangeTransfer $cartChangeTransfer): CartChangeTransfer
    {
        $cartChangeTransferForRemoval = $this->createCartChangeTransfer();

        foreach ($cartChangeTransfer->getItems() as $changeItem) {
            $quoteItem = $this->findItem($changeItem->getSku(), $changeItem->getGroupKey());

            if (!$quoteItem) {
                continue;
            }

            if ($changeItem->getQuantity() === 0) {
                $cartChangeTransferForRemoval->addItem($quoteItem);

                continue;
            }

            $delta = $changeItem->getQuantity() - $quoteItem->getQuantity();

            if ($delta >= 0) {
                continue;
            }

            $changeItemTransfer = clone $quoteItem;
            $changeItemTransfer->setQuantity(abs($delta));

            $cartChangeTransferForRemoval->addItem($changeItemTransfer);
        }

        if (!$cartChangeTransferForRemoval->getItems()->count()) {
            return $cartChangeTransferForRemoval;
        }

        return $this->cartChangeRequestExpander->removeItemRequestExpand($cartChangeTransferForRemoval);
    }

    protected function createCartChangeTransfer(): CartChangeTransfer
    {
        $quoteTransfer = $this->quoteClient->getQuote();

        if (count($quoteTransfer->getItems()) === 0) {
            $quoteTransfer->setItems(new ArrayObject());
        }

        return (new CartChangeTransfer())
            ->setQuote($quoteTransfer);
    }

    /**
     * @param string $sku
     * @param string|null $groupKey
     *
     * @return \Generated\Shared\Transfer\ItemTransfer|null
     */
    protected function findItem($sku, $groupKey = null): ?ItemTransfer
    {
        $quoteTransfer = $this->quoteClient->getQuote();

        return $this->quoteItemFinderPlugin->findItem($quoteTransfer, $sku, $groupKey);
    }
}
