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

/**
 * Creates a unique address which should be presented to the customer.
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @url https://blockchain.info/api/api_receive
 * @package vSymfoPaymentBlockchainBundle
 */
class ReceivingResponse
{
    /**
     * @var integer
     */
    private $feePercent = null;

    /**
     * The destination bitcoin address.
     * @var string
     */
    private $destination = null;

    /**
     * The bitcoin address that received the transaction.
     * @var srting
     */
    private $inputAddress = null;

    /**
     * The callback URL to be notified when a payment is received.
     * @var string
     */
    private $callbackUrl = null;

    /**
     * @param string $response odpowiedź serwera
     * @throws \Exception
     */
    public function __construct($response)
    {
        if (!is_string($response)) {
            throw new \InvalidArgumentException('$response is not string');
        }

        $data = json_decode($response, true);
        if (is_null($data) || !isset($data["destination"]) || !is_string($data["destination"])
            || !isset($data["input_address"]) || !is_string($data["input_address"])
            || !isset($data["fee_percent"]) || !is_scalar($data["fee_percent"])
        ) {
            throw new \Exception("Invalid response: $response", 1);
        }

        $this->destination = $data["destination"];
        $this->inputAddress = $data["input_address"];
        $this->feePercent = $data["fee_percent"];

        if (isset($data["callback_url"]) && is_scalar($data["callback_url"])) {
            $this->callbackUrl = $data["callback_url"];
        }
    }

    /**
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->callbackUrl;
    }

    /**
     * @return srting
     */
    public function getInputAddress()
    {
        return $this->inputAddress;
    }

    /**
     * @return int
     */
    public function getFeePercent()
    {
        return $this->feePercent;
    }

    /**
     * @return string
     */
    public function getDestination()
    {
        return $this->destination;
    }
}
