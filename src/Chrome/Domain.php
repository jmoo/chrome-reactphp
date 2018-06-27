<?php

namespace Jmoo\React\Chrome;

use Jmoo\React\Support\AwaitablePromise;

class Domain
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $domain;

    public function __construct(ConnectionInterface $connection, $domain)
    {
        $this->connection = $connection;
        $this->domain = $domain;
    }

    public function enable(): AwaitablePromise
    {
        return $this->connection->enable([$this->domain])->then(function () {
            return $this;
        });
    }

    public function on($event, callable $callable): Domain
    {
        $this->connection->on($this->withDomain($event), $callable);
        return $this;
    }

    public function send($method, $args = []): AwaitablePromise
    {
        return $this->connection->send($this->withDomain($method), $args);
    }

    public function __call($method, $arguments): AwaitablePromise
    {
        $args = !empty($arguments) ? $arguments[0] : [];
        return $this->send($method, $args);
    }

    private function withDomain($symbol): string
    {
        return ($this->domain ? $this->domain . '.' : '') . $symbol;
    }
}