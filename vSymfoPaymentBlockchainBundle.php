<?php

/*
 * This file is part of the vSymfo package.
 *
 * website: www.vision-web.pl
 * (c) Rafał Mikołajun <rafal@vision-web.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace vSymfo\Payment\BlockchainBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use vSymfo\Payment\BlockchainBundle\DependencyInjection\vSymfoPaymentBlockchainExtension;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfoPaymentBlockchainBundle
 */
class vSymfoPaymentBlockchainBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $this->extension = new vSymfoPaymentBlockchainExtension();
        }

        return $this->extension;
    }
}
