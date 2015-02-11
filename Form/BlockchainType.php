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

namespace vSymfo\Payment\BlockchainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfoPaymentBlockchainBundle
 */
class BlockchainType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {}

    public function getName()
    {
        return 'blockchain_payment';
    }
}
