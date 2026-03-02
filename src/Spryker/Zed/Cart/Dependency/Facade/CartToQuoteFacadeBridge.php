<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Cart\Dependency\Facade;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\QuoteValidationResponseTransfer;

class CartToQuoteFacadeBridge implements CartToQuoteFacadeInterface
{
    /**
     * @var \Spryker\Zed\Quote\Business\QuoteFacadeInterface
     */
    protected $quoteFacade;

    /**
     * @param \Spryker\Zed\Quote\Business\QuoteFacadeInterface $quoteFacade
     */
    public function __construct($quoteFacade)
    {
        $this->quoteFacade = $quoteFacade;
    }

    public function isQuoteLocked(QuoteTransfer $quoteTransfer): bool
    {
        return $this->quoteFacade->isQuoteLocked($quoteTransfer);
    }

    public function unlockQuote(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        return $this->quoteFacade->unlockQuote($quoteTransfer);
    }

    public function validateQuote(QuoteTransfer $quoteTransfer): QuoteValidationResponseTransfer
    {
        return $this->quoteFacade->validateQuote($quoteTransfer);
    }
}
