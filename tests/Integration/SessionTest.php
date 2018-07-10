<?php

namespace Jmoo\React\Chrome\Tests\Integration;

use Jmoo\React\Chrome\Blocking\Connection;
use Jmoo\React\Chrome\ConnectionInterface;

class SessionTest extends AbstractConnectionTest
{
    protected function connect(): ConnectionInterface
    {
        $version = $this->client->version();

        /** @var Connection $connection */
        $connection = $this->client->connect($version->webSocketDebuggerUrl);
        $this->attachLoggerToConnection($connection);

        $target = $connection->send('Target.createTarget', ['url' => 'about:blank']);
        return $connection->createSession($target->targetId);
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