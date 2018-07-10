<?php

namespace Jmoo\React\Chrome\Tests\Integration;


use Jmoo\React\Chrome\Blocking\Client;
use Jmoo\React\Chrome\ConnectionInterface;


class ConnectionTest extends AbstractConnectionTest
{
    protected function connect(): ConnectionInterface
    {
        $client = new Client;
        $page = $client->new();
        $connection = $client->connect($page->webSocketDebuggerUrl);
        $this->attachLoggerToConnection($connection);
        return $connection;
    }

    /**
     * @group integration
     * @test
     */
    public function canSendMessageAndReceiveResponse()
    {
        $msg = $this->connection->send('Page.enable');
        $this->assertInstanceOf(\stdClass::class, $msg);
    }

    /**
     * @group integration
     * @test
     */
    public function canSendMessageToDomainAndReceiveResponse()
    {
        $msg = $this->connection->getDomain('Page')->send('enable');
        $this->assertInstanceOf(\stdClass::class, $msg);
    }
}