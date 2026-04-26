<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Cart;

use Spryker\Zed\Kernel\AbstractBundleConfig;

class CartConfig extends AbstractBundleConfig
{
    /**
     * @var string
     */
    public const OPERATION_ADD = 'add';

    /**
     * @var string
     */
    public const OPERATION_REMOVE = 'remove';

    /**
     * Specification:
     * - When true, addValid() pre-checks each item individually then adds all valid items in a single bulk addToCart call.
     * - When false, addValid() calls addToCart() per item sequentially (default, backward-compatible behavior).
     *
     * @api
     */
    public function isAddToCartBulkEnabled(): bool
    {
        return false;
    }
}
