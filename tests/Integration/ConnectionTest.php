<?php

namespace Jmoo\React\Chrome\Tests\Integration;

use Jmoo\React\Chrome\Client;
use Jmoo\React\Chrome\ConnectionInterface;
use Jmoo\React\Support\AwaitablePromise;
use React\Promise\Promise;

class ConnectionTest extends AbstractConnectionTest
{
    protected function connect(): ConnectionInterface
    {
        $timeout = AbstractConnectionTest::TIMEOUT;

        $client = new Client;
        $page = $client->new()->await($timeout);
        $connection = $client->connect($page->webSocketDebuggerUrl)->await($timeout);
        $this->attachLoggerToConnection($connection);
        return $connection;
    }

    /**
     * @group integration
     * @test
     */
    public function canSendMessageAndReceiveResponse()
    {
        $this->connection
            ->send('Page.enable')
            ->then(function ($msg) {
                $this->assertInstanceOf(\stdClass::class, $msg);
            })
            ->await(self::TIMEOUT);
    }

    /**
     * @group integration
     * @test
     */
    public function canSendMessageToDomainAndReceiveResponse()
    {
        $this->connection
            ->getDomain('Page')
            ->send('enable')
            ->then(function ($msg) {
                $this->assertInstanceOf(\stdClass::class, $msg);
            })
            ->await(self::TIMEOUT);
    }
}