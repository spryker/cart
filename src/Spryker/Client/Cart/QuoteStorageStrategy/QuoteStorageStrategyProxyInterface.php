<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Cart\QuoteStorageStrategy;

use Spryker\Client\CartExtension\Dependency\Plugin\QuoteResetLockQuoteStorageStrategyPluginInterface;
use Spryker\Client\CartExtension\Dependency\Plugin\QuoteStorageStrategyPluginInterface;
use Spryker\Client\CartExtension\Dependency\Plugin\ReplaceableQuoteItemStorageStrategyPluginInterface;

interface QuoteStorageStrategyProxyInterface extends QuoteStorageStrategyPluginInterface, QuoteResetLockQuoteStorageStrategyPluginInterface, ReplaceableQuoteItemStorageStrategyPluginInterface
{
}
