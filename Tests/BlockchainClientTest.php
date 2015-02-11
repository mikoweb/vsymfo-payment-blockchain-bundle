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

use vSymfo\Payment\BlockchainBundle\Client\BlockchainClient;

class BlockchainClientTest extends \PHPUnit_Framework_TestCase
{
    public function testClient()
    {
        $client = new BlockchainClient("1A8JiWcwvpY7tAopUkSnGuEYHmzGYfZPiq", "seCrEtKey");
        $this->assertEquals($client->getReceivingAddress(), "1A8JiWcwvpY7tAopUkSnGuEYHmzGYfZPiq");
        $this->assertEquals($client->generateCallbackUrl("http://yoururl.com"), "http://yoururl.com/?secret=seCrEtKey");
        $this->assertEquals($client->getSecretParameter(), "seCrEtKey");

        $response = $client->generatePayment("http://yoururl.com");
        $this->assertEquals($response->getDestination(), "1A8JiWcwvpY7tAopUkSnGuEYHmzGYfZPiq");
        $this->assertNotNull($response->getInputAddress());
        $this->assertEquals($response->getFeePercent(), 0);
        $this->assertEquals($response->getCallbackUrl(), "http://yoururl.com/?secret=seCrEtKey");

        try { // nieprawidłowa odpowiedź
            $client = new BlockchainClient("invalid address", "seCrEtKey");
            $client->generatePayment("http://yoururl.com");
            $this->assertTrue(false);
        } catch (\Exception $e) {
            if ($e->getCode() == 1) {
                $this->assertTrue(true);
            } else {
                $this->assertTrue(false);
            }
        }
    }
}
