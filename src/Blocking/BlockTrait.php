<?php

namespace Jmoo\React\Chrome\Blocking;

use function Clue\React\Block\await;
use Jmoo\React\Chrome\ClientInterface;
use React\Promise\PromiseInterface;

trait BlockTrait
{
    /**
     * @var int
     */
    protected $timeout = ClientInterface::DEFAULT_TIMEOUT;

    private function block(PromiseInterface $promise, $timeout = null)
    {
        return await($promise, $this->getLoop(), is_null($timeout) ? $this->timeout : $timeout);
    }
}