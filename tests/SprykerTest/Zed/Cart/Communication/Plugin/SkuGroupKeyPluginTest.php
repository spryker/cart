<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Cart\Communication\Plugin;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Spryker\Zed\Cart\Communication\Plugin\SkuGroupKeyPlugin;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Cart
 * @group Communication
 * @group Plugin
 * @group SkuGroupKeyPluginTest
 * Add your own group annotations below this line
 */
class SkuGroupKeyPluginTest extends Unit
{
    /**
     * @var string
     */
    public const SKU = 'sku';

    /**
     * @return void
     */
    public function testExpandItemMustSetGroupKeyToSkuOfGivenProductWhenNoGroupKeyIsSet(): void
    {
        $itemTransfer = new ItemTransfer();
        $itemTransfer->setSku(static::SKU);

        $changeTransfer = new CartChangeTransfer();
        $changeTransfer->addItem($itemTransfer);

        $plugin = new SkuGroupKeyPlugin();
        $plugin->expandItems($changeTransfer);

        $this->assertSame(static::SKU, $changeTransfer->getItems()[0]->getGroupKey());
    }

    /**
     * @return void
     */
    public function testExpandItemMustNotChangeGroupKeyWhenGroupKeyIsSet(): void
    {
        $itemTransfer = new ItemTransfer();
        $itemTransfer->setSku(static::SKU);
        $itemTransfer->setGroupKey(static::SKU);

        $changeTransfer = new CartChangeTransfer();
        $changeTransfer->addItem($itemTransfer);

        $plugin = new SkuGroupKeyPlugin();
        $plugin->expandItems($changeTransfer);

        $this->assertSame(static::SKU, $changeTransfer->getItems()[0]->getGroupKey());
    }
}
