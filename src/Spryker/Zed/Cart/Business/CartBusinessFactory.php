<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Cart\Business;

use Spryker\Zed\Cart\Business\Expander\GroupKeyExpander;
use Spryker\Zed\Cart\Business\Expander\GroupKeyExpanderInterface;
use Spryker\Zed\Cart\Business\Locker\QuoteLocker;
use Spryker\Zed\Cart\Business\Locker\QuoteLockerInterface;
use Spryker\Zed\Cart\Business\Model\Operation;
use Spryker\Zed\Cart\Business\Model\QuoteChangeObserver;
use Spryker\Zed\Cart\Business\Model\QuoteChangeObserverInterface;
use Spryker\Zed\Cart\Business\Model\QuoteCleaner;
use Spryker\Zed\Cart\Business\Model\QuoteCleanerInterface;
use Spryker\Zed\Cart\Business\Model\QuoteValidator;
use Spryker\Zed\Cart\Business\Replacer\CartItemReplacer;
use Spryker\Zed\Cart\Business\Replacer\CartItemReplacerInterface;
use Spryker\Zed\Cart\Business\StorageProvider\NonPersistentProvider;
use Spryker\Zed\Cart\Business\StorageProvider\StorageProviderInterface;
use Spryker\Zed\Cart\CartDependencyProvider;
use Spryker\Zed\Cart\Dependency\Facade\CartToQuoteFacadeInterface;
use Spryker\Zed\Cart\Dependency\Service\CartToUtilTextServiceInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;

/**
 * @method \Spryker\Zed\Cart\CartConfig getConfig()
 */
class CartBusinessFactory extends AbstractBusinessFactory
{
    /**
     * @return \Spryker\Zed\Cart\Business\Replacer\CartItemReplacerInterface
     */
    public function createCartItemReplacer(): CartItemReplacerInterface
    {
        return new CartItemReplacer($this->createCartOperation());
    }

    /**
     * @return \Spryker\Zed\Cart\Business\Model\OperationInterface
     */
    public function createCartOperation()
    {
        $operation = new Operation(
            $this->createStorageProvider(),
            $this->getCalculatorFacade(),
            $this->getMessengerFacade(),
            $this->getQuoteFacade(),
            $this->getItemExpanderPlugins(),
            $this->getCartPreCheckPlugins(),
            $this->getPostSavePlugins(),
            $this->getTerminationPlugins(),
            $this->getCartRemovalPreCheckPlugins(),
            $this->getPostReloadItemsPlugins(),
            $this->getCartBeforePreCheckNormalizerPlugins(),
        );

        $operation->setPreReloadLoadPlugins($this->getPreReloadItemsPlugins());

        return $operation;
    }

    /**
     * @return \Spryker\Zed\Cart\Business\Model\QuoteValidatorInterface
     */
    public function createQuoteValidator()
    {
        return new QuoteValidator(
            $this->createCartOperation(),
            $this->createQuoteChangeObserver(),
            $this->getMessengerFacade(),
            $this->getQuoteFacade(),
        );
    }

    /**
     * @return \Spryker\Zed\Cart\Business\Model\QuoteCleanerInterface
     */
    public function createQuoteCleaner(): QuoteCleanerInterface
    {
        return new QuoteCleaner();
    }

    /**
     * @return \Spryker\Zed\Cart\Business\Locker\QuoteLockerInterface
     */
    public function createQuoteLocker(): QuoteLockerInterface
    {
        return new QuoteLocker(
            $this->getQuoteFacade(),
            $this->createCartOperation(),
            $this->getQuoteLockPreResetPlugins(),
        );
    }

    /**
     * @return \Spryker\Zed\Cart\Business\StorageProvider\StorageProviderInterface
     */
    public function createStorageProvider(): StorageProviderInterface
    {
        return new NonPersistentProvider(
            $this->getCartAddItemStrategyPlugins(),
            $this->getCartRemoveItemStrategyPlugins(),
        );
    }

    /**
     * @return \Spryker\Zed\Cart\Business\Model\QuoteChangeObserverInterface
     */
    public function createQuoteChangeObserver(): QuoteChangeObserverInterface
    {
        return new QuoteChangeObserver($this->getMessengerFacade(), $this->getQuoteChangeObserverPlugins());
    }

    /**
     * @return \Spryker\Zed\Cart\Dependency\Facade\CartToCalculationInterface
     */
    protected function getCalculatorFacade()
    {
        return $this->getProvidedDependency(CartDependencyProvider::FACADE_CALCULATION);
    }

    /**
     * @return \Spryker\Zed\Cart\Dependency\Facade\CartToQuoteFacadeInterface
     */
    public function getQuoteFacade(): CartToQuoteFacadeInterface
    {
        return $this->getProvidedDependency(CartDependencyProvider::FACADE_QUOTE);
    }

    /**
     * @return \Spryker\Zed\Cart\Dependency\Facade\CartToMessengerInterface
     */
    protected function getMessengerFacade()
    {
        return $this->getProvidedDependency(CartDependencyProvider::FACADE_MESSENGER);
    }

    /**
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\ItemExpanderPluginInterface>
     */
    protected function getItemExpanderPlugins()
    {
        return $this->getProvidedDependency(CartDependencyProvider::CART_EXPANDER_PLUGINS);
    }

    /**
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\CartPreCheckPluginInterface>
     */
    protected function getCartPreCheckPlugins()
    {
        return $this->getProvidedDependency(CartDependencyProvider::CART_PRE_CHECK_PLUGINS);
    }

    /**
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\CartChangeTransferNormalizerPluginInterface>
     */
    public function getCartBeforePreCheckNormalizerPlugins(): array
    {
        return $this->getProvidedDependency(CartDependencyProvider::CART_BEFORE_PRE_CHECK_NORMALIZER_PLUGINS);
    }

    /**
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\CartRemovalPreCheckPluginInterface>
     */
    public function getCartRemovalPreCheckPlugins()
    {
        return $this->getProvidedDependency(CartDependencyProvider::CART_REMOVAL_PRE_CHECK_PLUGINS);
    }

    /**
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\CartOperationPostSavePluginInterface>
     */
    protected function getPostSavePlugins()
    {
        return $this->getProvidedDependency(CartDependencyProvider::CART_POST_SAVE_PLUGINS);
    }

    /**
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\PreReloadItemsPluginInterface>
     */
    protected function getPreReloadItemsPlugins()
    {
        return $this->getProvidedDependency(CartDependencyProvider::CART_PRE_RELOAD_PLUGINS);
    }

    /**
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\CartTerminationPluginInterface>
     */
    protected function getTerminationPlugins()
    {
        return $this->getProvidedDependency(CartDependencyProvider::CART_TERMINATION_PLUGINS);
    }

    /**
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\QuoteChangeObserverPluginInterface>
     */
    protected function getQuoteChangeObserverPlugins(): array
    {
        return $this->getProvidedDependency(CartDependencyProvider::PLUGINS_QUOTE_CHANGE_OBSERVER);
    }

    /**
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\CartOperationStrategyPluginInterface>
     */
    public function getCartAddItemStrategyPlugins(): array
    {
        return $this->getProvidedDependency(CartDependencyProvider::PLUGINS_CART_ADD_ITEM_STRATEGY);
    }

    /**
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\CartOperationStrategyPluginInterface>
     */
    public function getCartRemoveItemStrategyPlugins(): array
    {
        return $this->getProvidedDependency(CartDependencyProvider::PLUGINS_CART_REMOVE_ITEM_STRATEGY);
    }

    /**
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\PostReloadItemsPluginInterface>
     */
    public function getPostReloadItemsPlugins(): array
    {
        return $this->getProvidedDependency(CartDependencyProvider::PLUGINS_POST_RELOAD_ITEMS);
    }

    /**
     * @return array<\Spryker\Zed\CartExtension\Dependency\Plugin\QuoteLockPreResetPluginInterface>
     */
    public function getQuoteLockPreResetPlugins(): array
    {
        return $this->getProvidedDependency(CartDependencyProvider::PLUGINS_QUOTE_LOCK_PRE_RESET);
    }

    /**
     * @return \Spryker\Zed\Cart\Business\Expander\GroupKeyExpanderInterface
     */
    public function createGroupKeyExpander(): GroupKeyExpanderInterface
    {
        return new GroupKeyExpander($this->getUtilTextService());
    }

    /**
     * @return \Spryker\Zed\Cart\Dependency\Service\CartToUtilTextServiceInterface
     */
    public function getUtilTextService(): CartToUtilTextServiceInterface
    {
        return $this->getProvidedDependency(CartDependencyProvider::SERVICE_UTIL_TEXT);
    }
}
