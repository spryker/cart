<?php
/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace SprykerFeature\Yves\Cart\Model;

interface CartCountInterface
{
    /**
     * @param int $count
     */
    public function setCount($count);

    /**
     * @return int
     */
    public function getCount();
}