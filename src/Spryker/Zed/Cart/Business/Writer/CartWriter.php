<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Cart\Business\Writer;

use Generated\Shared\Transfer\CartItemReplaceTransfer;
use Generated\Shared\Transfer\QuoteResponseTransfer;
use Spryker\Zed\Cart\Business\Model\OperationInterface;

class CartWriter implements CartWriterInterface
{
    /**
     * @var \Spryker\Zed\Cart\Business\Model\OperationInterface
     */
    protected $operation;

    /**
     * @param \Spryker\Zed\Cart\Business\Model\OperationInterface $operation
     */
    public function __construct(OperationInterface $operation)
    {
        $this->operation = $operation;
    }

    public function replaceItem(CartItemReplaceTransfer $cartItemReplaceTransfer): QuoteResponseTransfer
    {
        $cartItemReplaceTransfer->requireCartChangeForAdding()->requireCartChangeForRemoval();

        $quoteResponseTransfer = $this->operation->removeFromCart($cartItemReplaceTransfer->getCartChangeForRemoval());

        if (!$quoteResponseTransfer->getIsSuccessful()) {
            return $quoteResponseTransfer;
        }

        $cartChangeTransferForAdding = $cartItemReplaceTransfer->getCartChangeForAdding();

        $cartChangeTransferForAdding->setQuote($quoteResponseTransfer->getQuoteTransfer());

        return $this->operation->addToCart($cartChangeTransferForAdding);
    }
}
