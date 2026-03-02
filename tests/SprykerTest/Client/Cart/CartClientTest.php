<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\Cart;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Client\Cart\CartChangeRequestExpander\CartChangeRequestExpander;
use Spryker\Client\Cart\CartClient;
use Spryker\Client\Cart\Dependency\Client\CartToQuoteInterface;
use Spryker\Client\Cart\Plugin\ItemCountPlugin;
use Spryker\Client\Cart\Plugin\SessionQuoteStorageStrategyPlugin;
use Spryker\Client\Cart\Plugin\SimpleProductQuoteItemFinderPlugin;
use Spryker\Client\Cart\Zed\CartStubInterface;
use Spryker\Client\CartExtension\Dependency\Plugin\QuoteStorageStrategyPluginInterface;
use Spryker\Client\Kernel\AbstractFactory;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group Cart
 * @group CartClientTest
 * Add your own group annotations below this line
 */
class CartClientTest extends Unit
{
    /**
     * @var string
     */
    protected const PARAM_SEPARATE_PRODUCT = 'separate_product';

    /**
     * @var string
     */
    protected const ITEM_SKU = 'sku';

    /**
     * @var \SprykerTest\Client\Cart\CartClientTest
     */
    protected $tester;

    public function testGetCartMustReturnInstanceOfQuoteTransfer(): void
    {
        $quoteTransfer = new QuoteTransfer();
        $quoteMock = $this->getQuoteMock();
        $quoteMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteTransfer);

        $factoryMock = $this->getFactoryMock($quoteMock);
        $cartClientMock = $this->getCartClientMock($factoryMock);

        $this->assertSame($quoteTransfer, $cartClientMock->getQuote());
    }

    public function testClearCartMustSetItemCountInSessionToZero(): void
    {
        $quoteMock = $this->getQuoteMock();
        $quoteMock->expects($this->once())
            ->method('clearQuote')
            ->willReturn($quoteMock);

        $factoryMock = $this->getFactoryMock($quoteMock);
        $cartClientMock = $this->getCartClientMock($factoryMock);

        $cartClientMock->clearQuote();
    }

    public function testClearCartMustSetCartTransferInSessionToAnEmptyInstance(): void
    {
        $quoteMock = $this->getQuoteMock();
        $quoteMock->expects($this->once())
            ->method('clearQuote')
            ->willReturn($quoteMock);

        $factoryMock = $this->getFactoryMock($quoteMock);
        $cartClientMock = $this->getCartClientMock($factoryMock);

        $cartClientMock->clearQuote();
    }

    public function testAddItemMustOnlyExceptTransferInterfaceAsArgument(): void
    {
        $itemTransfer = new ItemTransfer();
        $quoteTransfer = new QuoteTransfer();
        $quoteMock = $this->getQuoteMock();
        $quoteMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteTransfer);

        $stubMock = $this->getStubMock();
        $stubMock->expects($this->once())
            ->method('addItem')
            ->willReturn($quoteTransfer);

        $sessionQuoteStorageStrategyPluginMock = $this->getSessionQuoteStorageStrategyPluginMock();
        $factoryMock = $this->getFactoryMock($quoteMock, $stubMock, $sessionQuoteStorageStrategyPluginMock);
        $cartClientMock = $this->getCartClientMock($factoryMock);

        $quoteTransfer = $cartClientMock->addItem($itemTransfer);

        $this->assertInstanceOf(QuoteTransfer::class, $quoteTransfer);
    }

    public function testChangeItemQuantityMustCallRemoveItemQuantityWhenPassedItemQuantityIsLowerThenInCartGivenItem(): void
    {
        $itemTransfer = new ItemTransfer();
        $itemTransfer->setQuantity(2);
        $itemTransfer->setSku('sku');

        $quoteTransfer = new QuoteTransfer();
        $quoteTransfer->addItem($itemTransfer);

        $quoteMock = $this->getQuoteMock();
        $quoteMock->expects($this->exactly(3))
            ->method('getQuote')
            ->willReturn($quoteTransfer);

        $stubMock = $this->getStubMock();
        $stubMock->expects($this->once())
            ->method('removeItem')
            ->willReturn($quoteTransfer);
        $stubMock->expects($this->never())
            ->method('addItem')
            ->willReturn($quoteTransfer);

        $sessionQuoteStorageStrategyPluginMock = $this->getSessionQuoteStorageStrategyPluginMock();
        $factoryMock = $this->getFactoryMock($quoteMock, $stubMock, $sessionQuoteStorageStrategyPluginMock);
        $cartClientMock = $this->getCartClientMock($factoryMock);

        $itemTransfer = new ItemTransfer();
        $itemTransfer->setSku('sku');

        $quoteTransfer = $cartClientMock->changeItemQuantity('sku', null, 1);

        $this->assertInstanceOf(QuoteTransfer::class, $quoteTransfer);
    }

    public function testChangeItemQuantityMustCallAddItemQuantityWhenPassedItemQuantityIsLowerThenInCartGivenItem(): void
    {
        $itemTransfer = new ItemTransfer();
        $itemTransfer->setQuantity(1);
        $itemTransfer->setSku('sku');

        $quoteTransfer = new QuoteTransfer();
        $quoteTransfer->addItem($itemTransfer);

        $quoteMock = $this->getQuoteMock();
        $quoteMock->expects($this->exactly(3))
            ->method('getQuote')
            ->willReturn($quoteTransfer);

        $stubMock = $this->getStubMock();
        $stubMock->expects($this->never())
            ->method('removeItem')
            ->willReturn($quoteTransfer);

        $stubMock->expects($this->once())
            ->method('addItem')
            ->willReturn($quoteTransfer);
        $sessionQuoteStorageStrategyPluginMock = $this->getSessionQuoteStorageStrategyPluginMock();
        $factoryMock = $this->getFactoryMock($quoteMock, $stubMock, $sessionQuoteStorageStrategyPluginMock);
        $cartClientMock = $this->getCartClientMock($factoryMock);

        $itemTransfer = new ItemTransfer();
        $itemTransfer->setSku('sku');

        $quoteTransfer = $cartClientMock->changeItemQuantity('sku', null, 2);

        $this->assertInstanceOf(QuoteTransfer::class, $quoteTransfer);
    }

    public function testGetItemCountReturnNumberOfItemsInCart(): void
    {
        $itemTransfer = new ItemTransfer();
        $itemTransfer->setQuantity(1);
        $itemTransfer->setSku('sku');

        $quoteTransfer = new QuoteTransfer();
        $quoteTransfer->addItem($itemTransfer);

        $mockBuilder = $this->getMockBuilder(CartClient::class);
        $mockBuilder->onlyMethods(['getQuote', 'getItemCounter']);
        /** @var \Spryker\Client\Cart\CartClientInterface|\PHPUnit\Framework\MockObject\MockObject $cartClientMock */
        $cartClientMock = $mockBuilder->getMock();
        $cartClientMock->method('getQuote')->willReturn($quoteTransfer);
        $cartClientMock->method('getItemCounter')->willReturn(new ItemCountPlugin());

        $this->assertSame(1, $cartClientMock->getItemCount());
    }

    public function testExpandCartItemsWithGroupKeyPrefixWhenParamSeparateProductProvided(): void
    {
        // Arrange
        $cartChangeTransfer = $this->createCartChangeTransfer();
        $params = [static::PARAM_SEPARATE_PRODUCT => 1];

        // Act
        $cartChangeTransfer = $this->tester->getClient()->expandCartItemsWithGroupKeyPrefix($cartChangeTransfer, $params);

        // Assert
        /** @var \Generated\Shared\Transfer\ItemTransfer $expandCartItem */
        $expandCartItem = $cartChangeTransfer->getItems()->getIterator()->current();
        $this->assertNotNull($expandCartItem->getGroupKeyPrefix());
    }

    public function testExpandCartItemsWithGroupKeyPrefixWhenParamSeparateProductNotProvided(): void
    {
        // Arrange
        $cartChangeTransfer = $this->createCartChangeTransfer();

        // Act
        $cartChangeTransfer = $this->tester->getClient()->expandCartItemsWithGroupKeyPrefix($cartChangeTransfer, []);

        // Assert
        /** @var \Generated\Shared\Transfer\ItemTransfer $expandCartItem */
        $expandCartItem = $cartChangeTransfer->getItems()->getIterator()->current();
        $this->assertNull($expandCartItem->getGroupKeyPrefix());
    }

    /**
     * @param \Spryker\Client\Cart\Dependency\Client\CartToQuoteInterface|null $quote
     * @param \Spryker\Client\Cart\Zed\CartStubInterface|null $cartStub
     * @param \Spryker\Client\CartExtension\Dependency\Plugin\QuoteStorageStrategyPluginInterface|null $quoteStorageStrategyPlugin
     *
     * @return \Spryker\Client\Kernel\AbstractFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getFactoryMock(
        ?CartToQuoteInterface $quote = null,
        ?CartStubInterface $cartStub = null,
        ?QuoteStorageStrategyPluginInterface $quoteStorageStrategyPlugin = null
    ): AbstractFactory {
        $factoryMock = $this->getMockBuilder(CartClientTestFactoryPrototype::class)
            ->disableOriginalConstructor()
            ->getMock();

        if ($quote !== null) {
            $factoryMock->expects($this->any())
                ->method('getQuoteClient')
                ->willReturn($quote);
        }
        if ($cartStub !== null) {
            $factoryMock->expects($this->any())
                ->method('createZedStub')
                ->willReturn($cartStub);
        }
        if ($cartStub !== null) {
            $quoteStorageStrategyPlugin->expects($this->any())
                ->method('getFactory')
                ->willReturn($factoryMock);
            $factoryMock->expects($this->any())
                ->method('createQuoteStorageStrategyProxy')
                ->willReturn($quoteStorageStrategyPlugin);
        }

        $factoryMock->expects($this->any())
            ->method('createCartChangeRequestExpander')
            ->willReturn(new CartChangeRequestExpander([], []));

        $factoryMock->expects($this->any())
            ->method('getQuoteItemFinderPlugin')
            ->willReturn(new SimpleProductQuoteItemFinderPlugin());

        return $factoryMock;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Client\CartExtension\Dependency\Plugin\QuoteStorageStrategyPluginInterface
     */
    private function getSessionQuoteStorageStrategyPluginMock(): QuoteStorageStrategyPluginInterface
    {
        $sessionQuoteStorageStrategyPluginMock = $this->getMockBuilder(SessionQuoteStorageStrategyPlugin::class)
            ->onlyMethods(['getFactory'])->disableOriginalConstructor()->getMock();

        return $sessionQuoteStorageStrategyPluginMock;
    }

    /**
     * @param \Spryker\Client\Kernel\AbstractFactory|\PHPUnit\Framework\MockObject\MockObject $factoryMock
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Client\Cart\CartClient
     */
    private function getCartClientMock($factoryMock): CartClient
    {
        $cartClientMock = $this->getMockBuilder(CartClient::class)->onlyMethods(['getFactory'])->disableOriginalConstructor()->getMock();

        $cartClientMock->expects($this->any())
            ->method('getFactory')
            ->willReturn($factoryMock);

        return $cartClientMock;
    }

    /**
     * @return \Spryker\Client\Cart\Dependency\Client\CartToQuoteInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getQuoteMock(): CartToQuoteInterface
    {
        $quoteMock = $this->getMockBuilder(CartToQuoteInterface::class)
            ->onlyMethods([
            'getQuote',
            'setQuote',
            'clearQuote',
            'getStorageStrategy',
            'isQuoteLocked',
            'lockQuote',
        ])->getMock();

        $quoteMock->method('getStorageStrategy')
            ->willReturn('session');

        return $quoteMock;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Client\Cart\Zed\CartStubInterface
     */
    private function getStubMock(): CartStubInterface
    {
        return $this->getMockBuilder(CartStubInterface::class)
            ->onlyMethods([
                'addValidItems',
                'addItem',
                'removeItem',
                'reloadItems',
                'validateQuote',
                'resetQuoteLock',
                'addToCart',
                'removeFromCart',
                'replaceItem',
        ])->getMock();
    }

    protected function createCartChangeTransfer(): CartChangeTransfer
    {
        $cartItem = new ItemTransfer();
        $cartItem->setSku(static::ITEM_SKU);

        $quoteTransfer = new QuoteTransfer();
        $quoteTransfer->addItem($cartItem);

        $cartChangeTransfer = new CartChangeTransfer();
        $cartChangeTransfer->setQuote($quoteTransfer);
        $cartChangeTransfer->addItem($cartItem);

        return $cartChangeTransfer;
    }
}
