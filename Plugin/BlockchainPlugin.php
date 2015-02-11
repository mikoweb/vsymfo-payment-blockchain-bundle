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

namespace vSymfo\Payment\BlockchainBundle\Plugin;

use EgoPaySci;
use JMS\Payment\CoreBundle\Model\ExtendedDataInterface;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Plugin\AbstractPlugin;
use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;
use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
use JMS\Payment\CoreBundle\Plugin\Exception\BlockedException;
use JMS\Payment\CoreBundle\Plugin\Exception\FinancialException;
use JMS\Payment\CoreBundle\Plugin\PluginInterface;
use JMS\Payment\CoreBundle\Util\Number;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Router;
use vSymfo\Component\Payments\EventDispatcher\PaymentEvent;
use vSymfo\Payment\BlockchainBundle\Client\BlockchainClient;

/**
 * Plugin płatności bitcoin Blockchain
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfoPaymentBlockchainBundle
 */
class BlockchainPlugin extends AbstractPlugin
{
    /**
     * @var BlockchainClient
     */
    private $client;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @param Router $router The router
     * @param BlockchainClient $client
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(Router $router, BlockchainClient $client, EventDispatcherInterface $dispatcher)
    {
        $this->client = $client;
        $this->router = $router;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Nazwa płatności
     * @return string
     */
    public function getName()
    {
        return 'blockchain_payment';
    }

    /**
     * {@inheritdoc}
     */
    public function processes($name)
    {
        return $this->getName() === $name;
    }

    /**
     * {@inheritdoc}
     */
    public function approveAndDeposit(FinancialTransactionInterface $transaction, $retry)
    {
        if ($transaction->getState() === FinancialTransactionInterface::STATE_NEW) {
            throw $this->createPaymentRedirect($transaction);
        }

        $this->approve($transaction, $retry);
        $this->deposit($transaction, $retry);
    }

    /**
     * @param FinancialTransactionInterface $transaction
     * @return ActionRequiredException
     * @throws FinancialException
     */
    public function createPaymentRedirect(FinancialTransactionInterface $transaction)
    {
        $actionRequest = new ActionRequiredException('Redirecting to payment.');
        $actionRequest->setFinancialTransaction($transaction);
        $instruction = $transaction->getPayment()->getPaymentInstruction();

        if ($instruction->getCurrency() != "BTC") {
            $e = new FinancialException("Transaction currency is not BTC");
            $e->setFinancialTransaction($transaction);
            throw $e;
        }

        $data = $transaction->getExtendedData();
        $response = $this->client->generatePayment($this->router->generate('vsymfo_payment_blockchain_callback', array(
            "id" => $instruction->getId()
        ), true));

        $data->set("generated_input_address", $response->getInputAddress());

        $actionRequest->setAction(new VisitUrl($this->router->generate('vsymfo_payment_blockchain_redirect', array(
            "id" => $instruction->getId()
        ))));

        return $actionRequest;
    }

    /**
     * Check that the extended data contains the needed values
     * before approving and depositing the transation
     *
     * @param ExtendedDataInterface $data
     * @throws BlockedException
     */
    protected function checkExtendedDataBeforeApproveAndDeposit(ExtendedDataInterface $data)
    {
        if (!$data->has('confirmations') || !$data->has('value') || !$data->has('input_address') || !$data->has('destination_address') || !$data->has('transaction_hash')) {
            throw new BlockedException("Awaiting extended data from Blockchain.info.");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function approve(FinancialTransactionInterface $transaction, $retry)
    {
        $data = $transaction->getExtendedData();
        $this->checkExtendedDataBeforeApproveAndDeposit($data);

        if ((int)$data->get('confirmations') >= 6) {
            $transaction->setReferenceNumber($data->get('transaction_hash'));
            $transaction->setProcessedAmount($data->get('value'));
            $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
            $transaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS);
        } else {
            $e = new FinancialException('Payment status unknow: ' . $data->get('confirmations'));
            $e->setFinancialTransaction($transaction);
            $transaction->setResponseCode('Unknown');
            $transaction->setReasonCode($data->get('confirmations'));
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deposit(FinancialTransactionInterface $transaction, $retry)
    {
        $data = $transaction->getExtendedData();

        if ($transaction->getResponseCode() !== PluginInterface::RESPONSE_CODE_SUCCESS
            || $transaction->getReasonCode() !== PluginInterface::REASON_CODE_SUCCESS
        ) {
            $e = new FinancialException('Peyment is not completed');
            $e->setFinancialTransaction($transaction);
            throw $e;
        }

        // różnica kwoty zatwierdzonej i kwoty wymaganej musi być równa zero
        // && nazwa waluty musi się zgadzać
        if (Number::compare($transaction->getProcessedAmount(), $transaction->getRequestedAmount()) === 0
            && $transaction->getPayment()->getPaymentInstruction()->getCurrency() == "BTC"
        ) {
            // wszystko ok
            // można zakakceptować zamówienie
            $event = new PaymentEvent($this->getName(), $transaction, $transaction->getPayment()->getPaymentInstruction());
            $this->dispatcher->dispatch('deposit', $event);
        } else {
            // coś się nie zgadza, nie można tego zakaceptować
            $e = new FinancialException('The deposit has not passed validation');
            $e->setFinancialTransaction($transaction);
            $transaction->setResponseCode('Unknown');
            $transaction->setReasonCode($data->get('confirmations'));
            throw $e;
        }
    }
}
