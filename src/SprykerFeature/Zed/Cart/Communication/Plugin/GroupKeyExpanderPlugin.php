<?php
/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace SprykerFeature\Zed\Cart\Communication\Plugin;

use Generated\Shared\Transfer\GroupKeyParameterTransfer;
use SprykerFeature\Zed\Cart\Dependency\ItemExpanderPluginInterface;
use SprykerEngine\Zed\Kernel\Communication\AbstractPlugin;
use Generated\Shared\Cart\ChangeInterface;
use SprykerFeature\Zed\Cart\Business\CartFacade;

/**
 * @method CartFacade getFacade()
 */
class GroupKeyExpanderPlugin extends AbstractPlugin implements ItemExpanderPluginInterface
{
    /**
     * @param ChangeInterface $change
     *
     * @return ChangeInterface
     */
    public function expandItems(ChangeInterface $change)
    {
        foreach ($change->getItems() as $cartItem) {
            $groupKeyPamaremeterTransfer = new GroupKeyParameterTransfer();
            $groupKeyPamaremeterTransfer->setSku($cartItem->getSku());
            $groupKeyPamaremeterTransfer->setName($cartItem->getName());

            $groupKey = $this->getFacade()->buildGroupKey($groupKeyPamaremeterTransfer);
            $cartItem->setGroupKey($groupKey);
        }

        return $change;
    }
}

