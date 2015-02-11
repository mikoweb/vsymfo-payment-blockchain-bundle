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

namespace vSymfo\Payment\BlockchainBundle\Controller;

use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use vSymfo\Payment\BlockchainBundle\Client\CallbackResponse;

/**
 * Kontroler płatności
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfoPaymentBlockchainBundle
 */
class PaymentController extends Controller
{
    /**
     * Zatwierdzanie płatności
     * @param Request $request
     * @param PaymentInstruction $instruction
     * @return Response
     * @throws \Exception
     */
    public function callbackAction(Request $request, PaymentInstruction $instruction)
    {
        if (null === $transaction = $instruction->getPendingTransaction()) {
            throw new \RuntimeException('No pending transaction found for the payment instruction');
        }

        $client = $this->get('payment.blockchain.client');
        $response = new CallbackResponse($request);
        $extendedData = $transaction->getExtendedData();

        if ($client->getSecretParameter() !== $response->getSecretParameter()) {
            throw new \Exception("Invalid secret parameter", 1);
        }

        if ($client->getReceivingAddress() !== $response->getDestinationAddress()) {
            throw new \Exception("Invalid destination address", 2);
        }

        if ($extendedData->get("generated_input_address") !== $response->getInputAddress()) {
            throw new \Exception("Invalid input address", 3);
        }

        if ((int)$response->getConfirmations() < 6) {
            throw new \Exception("Not enough confirmations.", 4);
        }

        $amount = (float)$response->getValue() / 100000000; // The value of the payment received in satoshi. Divide by 100000000 to get the value in BTC.
        $em = $this->getDoctrine()->getManager();
        $extendedData->set("value", $amount);
        $extendedData->set("input_address", $response->getInputAddress());
        $extendedData->set("confirmations", $response->getConfirmations());
        $extendedData->set("transaction_hash", $response->getTransactionHash());
        $extendedData->set("input_transaction_hash", $response->getInputTransactionHash());
        $extendedData->set("destination_address", $response->getDestinationAddress());
        $em->persist($transaction);

        $payment = $transaction->getPayment();
        $result = $this->get('payment.plugin_controller')->approveAndDeposit($payment->getId(), $amount);
        if (is_object($ex = $result->getPluginException())) {
            throw $ex;
        }

        $em->flush();

        return new Response('*ok*');
    }
}
