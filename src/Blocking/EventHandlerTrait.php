<?php

namespace Jmoo\React\Chrome\Blocking;

use Evenement\EventEmitterInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;

trait EventHandlerTrait
{
    use BlockTrait;

    public function on($event, callable $listener)
    {
        $this->getEmitter()->on($event, $listener);
        return $this;
    }

    public function once($event, callable $listener)
    {
        $promise = new Promise(function ($resolve) use ($event, $listener) {
            $this->getEmitter()->once($event, function ($result) use ($resolve, $listener) {
                $resolve($listener($result));
            });
        });

        $this->getEmitter()->once('close', [$promise, 'cancel']);

        return $this->block($promise);
    }

    public function removeListener($event, callable $listener)
    {
        $this->getEmitter()->removeListener($event, $listener);
    }

    public function removeAllListeners($event = null)
    {
        $this->getEmitter()->removeAllListeners($event);
    }

    public function listeners($event = null)
    {
        return $this->getEmitter()->listeners($event);
    }

    public function emit($event, array $arguments = [])
    {
        $this->getEmitter()->emit($event, $arguments);
    }

    protected abstract function getLoop(): LoopInterface;

    protected abstract function getEmitter(): EventEmitterInterface;
}