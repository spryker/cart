<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Cart\Zed;

use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\CartItemReplaceTransfer;
use Generated\Shared\Transfer\QuoteResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

interface CartStubInterface
{
    public function addValidItems(CartChangeTransfer $cartChangeTransfer): QuoteTransfer;

    /**
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function addItem(CartChangeTransfer $cartChangeTransfer);

    public function addToCart(CartChangeTransfer $cartChangeTransfer): QuoteResponseTransfer;

    public function replaceItem(CartItemReplaceTransfer $cartItemReplaceTransfer): QuoteResponseTransfer;

    /**
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function removeItem(CartChangeTransfer $cartChangeTransfer);

    public function removeFromCart(CartChangeTransfer $cartChangeTransfer): QuoteResponseTransfer;

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteTransfer
     */
    public function reloadItems(QuoteTransfer $quoteTransfer);

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\QuoteResponseTransfer
     */
    public function validateQuote(QuoteTransfer $quoteTransfer);

    public function resetQuoteLock(QuoteTransfer $quoteTransfer): QuoteResponseTransfer;
}
