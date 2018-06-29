<?php

namespace Jmoo\React\Chrome\Tests\Integration;

use Jmoo\React\Chrome\Connection;
use Jmoo\React\Chrome\ConnectionInterface;

class SessionTest extends AbstractConnectionTest
{
    protected function connect(): ConnectionInterface
    {
        $timeout = AbstractConnectionTest::TIMEOUT;
        $version = $this->client->version()->await($timeout);

        /** @var Connection $connection */
        $connection = $this->client->connect($version->webSocketDebuggerUrl)->await($timeout);
        $this->attachLoggerToConnection($connection);

        $target = $connection->send('Target.createTarget', ['url' => 'about:blank'])->await($timeout);
        return $connection->createSession($target->targetId)->await($timeout);
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