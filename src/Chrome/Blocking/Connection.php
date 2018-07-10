<?php

namespace Jmoo\React\Chrome\Blocking;

use Evenement\EventEmitterInterface;
use Jmoo\React\Chrome\ClientInterface;
use Jmoo\React\Chrome\ConnectionInterface;
use Jmoo\React\Chrome\DomainInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use Jmoo\React\Chrome\Async\Connection as AsyncConnection;

class Connection implements ConnectionInterface
{
    use EventHandlerTrait;

    /**
     * @var AsyncConnection
     */
    private $connection;

    /**
     * @var Deferred
     */
    private $deferred;

    public function __construct(ConnectionInterface $connection, int $timeout = ClientInterface::DEFAULT_TIMEOUT)
    {
        $this->deferred = new Deferred;
        $this->connection = $connection;
        $this->timeout = $timeout;

        $this->connection
            ->on('error', function ($error) {
                $this->deferred->reject($error);
            })
            ->on('close', function () {
                $this->deferred->resolve();
            });
    }

    public function waitUntilDisconnect(int $timeout = null)
    {
        return $this->block($this->deferred->promise(), $timeout);
    }

    public function send($method, $params = [], int $timeout = null): \stdClass
    {
        return $this->block($this->connection->send($method, $params), $timeout);
    }

    public function enable(array $domains, int $timeout = null): array
    {
        return $this->block($this->connection->enable($domains), $timeout);
    }

    public function disconnect()
    {
        $this->connection->disconnect();
    }

    public function createSession($targetId, int $timeout = null) : Connection
    {
        $session = $this->block($this->connection->createSession($targetId), $timeout);
        return new Connection($session, $this->timeout);
    }

    public function getDomain($name): DomainInterface
    {
        return new Domain($this->connection->getDomain($name), $this->timeout);
    }

    public function __get($name): DomainInterface
    {
        return $this->getDomain($name);
    }

    public function getLoop(): LoopInterface
    {
        return $this->connection->getLoop();
    }

    protected function getEmitter(): EventEmitterInterface
    {
        return $this->connection;
    }
}