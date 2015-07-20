<?php

/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace SprykerFeature\Zed\Cart;

use SprykerEngine\Zed\Kernel\AbstractBundleConfig;
use SprykerFeature\Zed\Cart\Dependency\ItemExpanderPluginInterface;

class CartConfig extends AbstractBundleConfig
{

    /**
     * @return ItemExpanderPluginInterface[]
     */
    public function getItemExpanderPlugins()
    {
        return [
            $this->getLocator()->cart()->pluginProductIdPlugin(),
            $this->getLocator()->cart()->pluginCartItemAbstractSkuPlugin(),
        ];
    }

}
