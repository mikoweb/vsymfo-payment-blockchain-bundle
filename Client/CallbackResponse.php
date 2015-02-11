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

namespace vSymfo\Payment\BlockchainBundle\Client;

use Symfony\Component\HttpFoundation\Request;

/**
 * When a payment is received blockchain.info will notify the callback URL passed using the create method.
 * The parameters will be supplied in a http GET request. The callback url is limited to 255 characters in length.
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @url https://blockchain.info/api/api_receive
 * @package vSymfoPaymentBlockchainBundle
 */
class CallbackResponse
{
    /**
     * A custom secret parameter should be included in the callback URL.
     * The secret will be passed back to the callback script when the callback is fired and should be check for validity.
     * @var string
     */
    private $secretParameter;

    /**
     * The value of the payment received in satoshi. Divide by 100000000 to get the value in BTC.
     * @var float
     */
    private $value;

    /**
     * The bitcoin address that received the transaction.
     * @var string
     */
    private $inputAddress;

    /**
     * The number of confirmations of this transaction.
     * @var integer
     */
    private $confirmations;

    /**
     * The transaction hash.
     * @var string
     */
    private $transactionHash;

    /**
     * The original paying in hash before forwarding.
     * @var string
     */
    private $inputTransactionHash;

    /**
     * The destination bitcoin address. Check this matches your address.
     * @var string
     */
    private $destinationAddress;

    /**
     * @param Request $request
     * @throws \Exception
     */
    public function __construct(Request $request)
    {
        $this->secretParameter = $request->request->get("secret");
        $this->value = $request->request->get("value");
        $this->inputAddress = $request->request->get("input_address");
        $this->confirmations = $request->request->get("confirmations");
        $this->transactionHash = $request->request->get("transaction_hash");
        $this->inputTransactionHash = $request->request->get("input_transaction_hash");
        $this->destinationAddress = $request->request->get("destination_address");
    }

    /**
     * @return string
     */
    public function getSecretParameter()
    {
        return $this->secretParameter;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getInputAddress()
    {
        return $this->inputAddress;
    }

    /**
     * @return int
     */
    public function getConfirmations()
    {
        return $this->confirmations;
    }

    /**
     * @return string
     */
    public function getTransactionHash()
    {
        return $this->transactionHash;
    }

    /**
     * @return string
     */
    public function getInputTransactionHash()
    {
        return $this->inputTransactionHash;
    }

    /**
     * @return string
     */
    public function getDestinationAddress()
    {
        return $this->destinationAddress;
    }
}
