<?php

namespace Jmoo\React\Chrome\Blocking;

use Jmoo\React\Chrome\ClientInterface;
use Jmoo\React\Chrome\DomainInterface;
use \Jmoo\React\Chrome\Async\Domain as AsyncDomain;
use React\EventLoop\LoopInterface;

class Domain implements DomainInterface
{
    use BlockTrait;

    /**
     * @var AsyncDomain
     */
    private $domain;

    public function __construct(AsyncDomain $domain, int $timeout = ClientInterface::DEFAULT_TIMEOUT)
    {
        $this->domain = $domain;
        $this->timeout = $timeout;
    }

    public function enable(int $timeout = null)
    {
        return $this->block($this->domain->enable(), $timeout);
    }

    public function on($event, callable $callable): DomainInterface
    {
        $this->domain->on($event, $callable);
        return $this;
    }

    public function send($method, $args = [], int $timeout = null)
    {
        return $this->block($this->domain->send($method, $args), $timeout);
    }

    public function getLoop(): LoopInterface
    {
        return $this->domain->getLoop();
    }

    public function __call($method, $arguments = [])
    {
        $args = array_shift($arguments);
        $timeout = !empty($arguments) ? $arguments[0] : null;

        return $this->block($this->domain->$method($args), $timeout);
    }
}