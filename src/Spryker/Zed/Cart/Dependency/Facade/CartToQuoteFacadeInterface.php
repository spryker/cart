<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Cart\Dependency\Facade;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\QuoteValidationResponseTransfer;

interface CartToQuoteFacadeInterface
{
    public function isQuoteLocked(QuoteTransfer $quoteTransfer): bool;

    public function unlockQuote(QuoteTransfer $quoteTransfer): QuoteTransfer;

    public function validateQuote(QuoteTransfer $quoteTransfer): QuoteValidationResponseTransfer;
}
