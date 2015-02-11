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
 * Klient blockchain.info
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @url https://blockchain.info/api/api_receive
 * @package vSymfoPaymentBlockchainBundle
 */
class BlockchainClient
{
    /**
     * Your Receiving Bitcoin Address (Where you would like the payment to be sent)
     * @var string
     */
    private $receivingAddress;

    /**
     * A custom secret parameter should be included in the callback URL.
     * The secret will be passed back to the callback script when the callback is fired and should be check for validity.
     * @var string
     */
    private $secretParameter;

    /**
     * @param string $receivingAddress
     * @param string $secretParameter
     * @throws \Exception
     */
    public function __construct($receivingAddress, $secretParameter)
    {
        if (!function_exists('curl_version')) {
            throw new \Exception('curl not found');
        }

        if (!is_string($receivingAddress)) {
            throw new \InvalidArgumentException('$receivingAddress is not string');
        }

        if (!is_string($secretParameter)) {
            throw new \InvalidArgumentException('$secretParameter is not string');
        }

        $this->receivingAddress = $receivingAddress;
        $this->secretParameter = $secretParameter;
    }

    /**
     * @return string
     */
    public function getReceivingAddress()
    {
        return $this->receivingAddress;
    }

    /**
     * @return string
     */
    public function getSecretParameter()
    {
        return $this->secretParameter;
    }

    /**
     * Generate the callback URL to be notified when a payment is received.
     * @param string $url
     * @return string
     */
    public function generateCallbackUrl($url)
    {
        if (!is_string($url)) {
            throw new \InvalidArgumentException('$url is not string');
        }

        return http_build_url($url,
            array('query' => 'secret=' . $this->getSecretParameter()),
            HTTP_URL_STRIP_AUTH | HTTP_URL_JOIN_PATH | HTTP_URL_JOIN_QUERY | HTTP_URL_STRIP_FRAGMENT
        );
    }

    /**
     * The callback URL to be notified when a payment is received.
     * @param string $callbackUrl
     * @return ReceivingResponse
     */
    public function generatePayment($callbackUrl)
    {
        if (!is_string($callbackUrl)) {
            throw new \InvalidArgumentException('$url is not string');
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_URL, "https://blockchain.info/pl/api/receive?method=create&address=" . $this->getReceivingAddress() . "&callback=" . urlencode($this->generateCallbackUrl($callbackUrl)));
        $response = curl_exec($curl);
        curl_close($curl);

        return new ReceivingResponse($response);
    }
}
