<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Cart\Replacer;

use ArrayObject;
use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\CartItemReplaceTransfer;
use Generated\Shared\Transfer\ItemReplaceTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteResponseTransfer;
use Spryker\Client\Cart\CartChangeRequestExpander\CartChangeRequestExpanderInterface;
use Spryker\Client\Cart\Dependency\Client\CartToQuoteInterface;
use Spryker\Client\Cart\Zed\CartStubInterface;
use Spryker\Client\CartExtension\Dependency\Plugin\QuoteItemFinderPluginInterface;

class CartItemReplacer implements CartItemReplacerInterface
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

    public function replaceItem(ItemReplaceTransfer $itemReplaceTransfer): QuoteResponseTransfer
    {
        $cartChangeTransferForRemoval = $this->prepareCartChangeTransferForRemoval($itemReplaceTransfer);
        $cartChangeTransferForAdding = $this->prepareCartChangeTransferForAdding($itemReplaceTransfer);

        $quoteResponseTransfer = $this->executeReplaceItem($cartChangeTransferForRemoval, $cartChangeTransferForAdding);

        if (!$quoteResponseTransfer->getIsSuccessful()) {
            return (new QuoteResponseTransfer())
                ->setQuoteTransfer($this->quoteClient->getQuote())
                ->setIsSuccessful(false);
        }

        $this->quoteClient->setQuote($quoteResponseTransfer->getQuoteTransfer());

        return $quoteResponseTransfer;
    }

    protected function executeReplaceItem(
        CartChangeTransfer $cartChangeTransferForRemoval,
        CartChangeTransfer $cartChangeTransferForAdding
    ): QuoteResponseTransfer {
        $quoteResponseTransfer = (new QuoteResponseTransfer())
            ->setQuoteTransfer($this->quoteClient->getQuote())
            ->setIsSuccessful(false);

        if (!$cartChangeTransferForAdding->getItems()->count() || !$cartChangeTransferForRemoval->getItems()->count()) {
            return $quoteResponseTransfer;
        }

        return $this->cartStub->replaceItem((new CartItemReplaceTransfer())
            ->setCartChangeForRemoval($cartChangeTransferForRemoval)
            ->setCartChangeForAdding($cartChangeTransferForAdding));
    }

    protected function prepareCartChangeTransferForAdding(ItemReplaceTransfer $itemReplaceTransfer): CartChangeTransfer
    {
        $cartChangeTransferForAdding = $this->createCartChangeTransfer();
        $cartChangeTransferForAdding->addItem($itemReplaceTransfer->getNewItem());

        return $this->cartChangeRequestExpander->addItemsRequestExpand($cartChangeTransferForAdding);
    }

    protected function prepareCartChangeTransferForRemoval(ItemReplaceTransfer $itemReplaceTransfer): CartChangeTransfer
    {
        $cartChangeTransferForRemoval = $this->createCartChangeTransfer();

        $itemToBeReplaced = $itemReplaceTransfer->getItemToBeReplaced();
        $quoteItem = $this->findItem($itemToBeReplaced->getSku(), $itemToBeReplaced->getGroupKey());

        if (!$quoteItem) {
            return $cartChangeTransferForRemoval;
        }

        $cartChangeTransferForRemoval->addItem(clone $quoteItem);

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

    protected function findItem(string $sku, ?string $groupKey = null): ?ItemTransfer
    {
        $quoteTransfer = $this->quoteClient->getQuote();

        return $this->quoteItemFinderPlugin->findItem($quoteTransfer, $sku, $groupKey);
    }
}
