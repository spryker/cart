<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Cart\QuoteStorageStrategy;

use ArrayObject;
use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\ItemReplaceTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Client\Cart\Dependency\Client\CartToMessengerClientInterface;
use Spryker\Client\Cart\Dependency\Client\CartToQuoteInterface;
use Spryker\Client\Cart\Exception\QuoteStorageStrategyPluginNotFound;
use Spryker\Client\CartExtension\Dependency\Plugin\CartOperationQuoteStorageStrategyPluginInterface;
use Spryker\Client\CartExtension\Dependency\Plugin\QuoteResetLockQuoteStorageStrategyPluginInterface;
use Spryker\Client\CartExtension\Dependency\Plugin\QuoteStorageStrategyPluginInterface;
use Spryker\Client\CartExtension\Dependency\Plugin\ReplaceableQuoteItemStorageStrategyPluginInterface;

class QuoteStorageStrategyProxy implements QuoteStorageStrategyProxyInterface
{
    /**
     * @var string
     */
    protected const GLOSSARY_KEY_LOCKED_CART_CHANGE_DENIED = 'cart.locked.change_denied';

    /**
     * @var \Spryker\Client\CartExtension\Dependency\Plugin\QuoteStorageStrategyPluginInterface
     */
    protected $quoteStorageStrategy;

    /**
     * @var \Spryker\Client\Cart\Dependency\Client\CartToMessengerClientInterface
     */
    protected $messengerClient;

    /**
     * @var \Spryker\Client\Cart\Dependency\Client\CartToQuoteInterface
     */
    protected $quoteClient;

    /**
     * @param \Spryker\Client\Cart\Dependency\Client\CartToMessengerClientInterface $messengerClient
     * @param \Spryker\Client\Cart\Dependency\Client\CartToQuoteInterface $quoteClient
     * @param \Spryker\Client\CartExtension\Dependency\Plugin\QuoteStorageStrategyPluginInterface $quoteStorageStrategy
     */
    public function __construct(
        CartToMessengerClientInterface $messengerClient,
        CartToQuoteInterface $quoteClient,
        QuoteStorageStrategyPluginInterface $quoteStorageStrategy
    ) {
        $this->messengerClient = $messengerClient;
        $this->quoteClient = $quoteClient;
        $this->quoteStorageStrategy = $quoteStorageStrategy;
    }

    /**
     * @return string
     */
    public function getStorageStrategy(): string
    {
        return $this->quoteStorageStrategy->getStorageStrategy();
    }

    /**
     * @return bool
     */
    protected function isQuoteLocked(): bool
    {
        return $this->quoteClient->isQuoteLocked($this->getQuote());
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     * @param array<string, mixed> $params
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function addItem(ItemTransfer $itemTransfer, array $params = []): QuoteTransfer
    {
        return $this->quoteStorageStrategy->addItem($itemTransfer, $params);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     * @param array<string, mixed> $params
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function addItems(array $itemTransfers, array $params = []): QuoteTransfer
    {
        return $this->quoteStorageStrategy->addItems($itemTransfers, $params);
    }

    /**
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     * @param array<string, mixed> $params
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function addValidItems(CartChangeTransfer $cartChangeTransfer, array $params = []): QuoteTransfer
    {
        return $this->quoteStorageStrategy->addValidItems($cartChangeTransfer, $params);
    }

    /**
     * @param string $sku
     * @param string|null $groupKey
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function removeItem($sku, $groupKey = null): QuoteTransfer
    {
        if ($this->isQuoteLocked()) {
            $this->addPermissionFailedMessage();

            return $this->getQuote();
        }

        return $this->quoteStorageStrategy->removeItem($sku, $groupKey);
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function removeItems(ArrayObject $itemTransfers): QuoteTransfer
    {
        if ($this->isQuoteLocked()) {
            $this->addPermissionFailedMessage();

            return $this->getQuote();
        }

        return $this->quoteStorageStrategy->removeItems($itemTransfers);
    }

    /**
     * @param string $sku
     * @param string|null $groupKey
     * @param int $quantity
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function changeItemQuantity($sku, $groupKey = null, $quantity = 1): QuoteTransfer
    {
        if ($this->isQuoteLocked()) {
            $this->addPermissionFailedMessage();

            return $this->getQuote();
        }

        return $this->quoteStorageStrategy->changeItemQuantity($sku, $groupKey, $quantity);
    }

    /**
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @throws \Spryker\Client\Cart\Exception\QuoteStorageStrategyPluginNotFound
     *
     * @return \Generated\Shared\Transfer\QuoteResponseTransfer
     */
    public function addToCart(CartChangeTransfer $cartChangeTransfer): QuoteResponseTransfer
    {
        if ($this->isQuoteLocked()) {
            $this->addPermissionFailedMessage();

            return $this->createNotSuccessfulQuoteResponseTransfer();
        }

        if (!$this->quoteStorageStrategy instanceof CartOperationQuoteStorageStrategyPluginInterface) {
            throw new QuoteStorageStrategyPluginNotFound(
                'Quote storage strategy should implement CartOperationQuoteStorageStrategyPluginInterface in order to use `addToCart` functionality.',
            );
        }

        return $this->quoteStorageStrategy->addToCart($cartChangeTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @throws \Spryker\Client\Cart\Exception\QuoteStorageStrategyPluginNotFound
     *
     * @return \Generated\Shared\Transfer\QuoteResponseTransfer
     */
    public function removeFromCart(CartChangeTransfer $cartChangeTransfer): QuoteResponseTransfer
    {
        if ($this->isQuoteLocked()) {
            $this->addPermissionFailedMessage();

            return $this->createNotSuccessfulQuoteResponseTransfer();
        }

        if (!$this->quoteStorageStrategy instanceof CartOperationQuoteStorageStrategyPluginInterface) {
            throw new QuoteStorageStrategyPluginNotFound(
                'Quote storage strategy should implement CartOperationQuoteStorageStrategyPluginInterface in order to use `removeFromCart` functionality.',
            );
        }

        return $this->quoteStorageStrategy->removeFromCart($cartChangeTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @throws \Spryker\Client\Cart\Exception\QuoteStorageStrategyPluginNotFound
     *
     * @return \Generated\Shared\Transfer\QuoteResponseTransfer
     */
    public function updateQuantity(CartChangeTransfer $cartChangeTransfer): QuoteResponseTransfer
    {
        if ($this->isQuoteLocked()) {
            $this->addPermissionFailedMessage();

            return $this->createNotSuccessfulQuoteResponseTransfer();
        }

        if (!$this->quoteStorageStrategy instanceof CartOperationQuoteStorageStrategyPluginInterface) {
            throw new QuoteStorageStrategyPluginNotFound(
                'Quote storage strategy should implement CartOperationQuoteStorageStrategyPluginInterface in order to use `updateQuantity` functionality.',
            );
        }

        return $this->quoteStorageStrategy->updateQuantity($cartChangeTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\ItemReplaceTransfer $itemReplaceTransfer
     *
     * @throws \Spryker\Client\Cart\Exception\QuoteStorageStrategyPluginNotFound
     *
     * @return \Generated\Shared\Transfer\QuoteResponseTransfer
     */
    public function replaceItem(ItemReplaceTransfer $itemReplaceTransfer): QuoteResponseTransfer
    {
        $itemReplaceTransfer
            ->requireItemToBeReplaced()
            ->requireNewItem();

        if ($this->isQuoteLocked()) {
            $this->addPermissionFailedMessage();

            return $this->createNotSuccessfulQuoteResponseTransfer();
        }

        if (!$this->quoteStorageStrategy instanceof ReplaceableQuoteItemStorageStrategyPluginInterface) {
            throw new QuoteStorageStrategyPluginNotFound(
                'Quote storage strategy should implement ReplaceableQuoteItemStorageStrategyPluginInterface in order to use `replaceItem` functionality.',
            );
        }

        return $this->quoteStorageStrategy->replaceItem($itemReplaceTransfer);
    }

    /**
     * @param string $sku
     * @param string|null $groupKey
     * @param int $quantity
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function decreaseItemQuantity($sku, $groupKey = null, $quantity = 1): QuoteTransfer
    {
        if ($this->isQuoteLocked()) {
            $this->addPermissionFailedMessage();

            return $this->getQuote();
        }

        return $this->quoteStorageStrategy->decreaseItemQuantity($sku, $groupKey, $quantity);
    }

    /**
     * @param string $sku
     * @param string|null $groupKey
     * @param int $quantity
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function increaseItemQuantity($sku, $groupKey = null, $quantity = 1): QuoteTransfer
    {
        if ($this->isQuoteLocked()) {
            $this->addPermissionFailedMessage();

            return $this->getQuote();
        }

        return $this->quoteStorageStrategy->increaseItemQuantity($sku, $groupKey, $quantity);
    }

    /**
     * @return void
     */
    public function reloadItems(): void
    {
        if ($this->isQuoteLocked()) {
            $this->addPermissionFailedMessage();

            return;
        }

        $this->quoteStorageStrategy->reloadItems();
    }

    /**
     * @return \Generated\Shared\Transfer\QuoteResponseTransfer
     */
    public function validateQuote(): QuoteResponseTransfer
    {
        return $this->quoteStorageStrategy->validateQuote();
    }

    /**
     * @param \Generated\Shared\Transfer\CurrencyTransfer $currencyTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteResponseTransfer
     */
    public function setQuoteCurrency(CurrencyTransfer $currencyTransfer): QuoteResponseTransfer
    {
        if ($this->isQuoteLocked()) {
            $this->messengerClient->addErrorMessage(static::GLOSSARY_KEY_LOCKED_CART_CHANGE_DENIED);

            return $this->createNotSuccessfulQuoteResponseTransfer();
        }

        return $this->quoteStorageStrategy->setQuoteCurrency($currencyTransfer);
    }

    /**
     * @throws \Spryker\Client\Cart\Exception\QuoteStorageStrategyPluginNotFound
     *
     * @return \Generated\Shared\Transfer\QuoteResponseTransfer
     */
    public function resetQuoteLock(): QuoteResponseTransfer
    {
        if (!$this->quoteStorageStrategy instanceof QuoteResetLockQuoteStorageStrategyPluginInterface) {
            throw new QuoteStorageStrategyPluginNotFound(
                'Quote storage strategy should implement QuoteResetLockQuoteStorageStrategyPluginInterface in order to use `resetQuoteLock` functionality.',
            );
        }

        return $this->quoteStorageStrategy->resetQuoteLock();
    }

    /**
     * @return \Generated\Shared\Transfer\QuoteResponseTransfer
     */
    protected function createNotSuccessfulQuoteResponseTransfer(): QuoteResponseTransfer
    {
        $quoteResponseTransfer = new QuoteResponseTransfer();

        $quoteResponseTransfer->setIsSuccessful(false);
        $quoteResponseTransfer->setQuoteTransfer($this->quoteClient->getQuote());
        $quoteResponseTransfer->setCustomer($this->quoteClient->getQuote()->getCustomer());

        return $quoteResponseTransfer;
    }

    /**
     * @return void
     */
    protected function addPermissionFailedMessage(): void
    {
        $this->messengerClient->addErrorMessage(static::GLOSSARY_KEY_LOCKED_CART_CHANGE_DENIED);
    }

    /**
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    protected function getQuote(): QuoteTransfer
    {
        return $this->quoteClient->getQuote();
    }
}
