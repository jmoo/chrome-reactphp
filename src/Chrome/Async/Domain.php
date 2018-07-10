<?php

namespace Jmoo\React\Chrome\Async;

use Jmoo\React\Chrome\DomainInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

class Domain implements DomainInterface
{
    /**
     * @var AbstractConnection
     */
    private $connection;

    /**
     * @var string
     */
    private $domain;

    public function __construct(AbstractConnection $connection, $domain)
    {
        $this->connection = $connection;
        $this->domain = $domain;
    }

    public function enable(): PromiseInterface
    {
        return $this->connection->enable([$this->domain])->then(function () {
            return $this;
        });
    }

    public function on($event, callable $callable): DomainInterface
    {
        $this->connection->on($this->withDomain($event), $callable);
        return $this;
    }

    public function send($method, $args = []): PromiseInterface
    {
        return $this->connection->send($this->withDomain($method), $args);
    }

    public function __call($method, $arguments): PromiseInterface
    {
        $args = !empty($arguments) ? $arguments[0] : [];
        return $this->send($method, $args);
    }

    private function withDomain($symbol): string
    {
        return ($this->domain ? $this->domain . '.' : '') . $symbol;
    }

    public function getLoop(): LoopInterface
    {
        return $this->connection->getLoop();
    }
}