<?php

namespace Jmoo\React\Support;

use function Clue\React\Block\await;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

class AwaitablePromise implements PromiseInterface
{
    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var PromiseInterface
     */
    private $promise;

    public function __construct(PromiseInterface $promise, LoopInterface $loop)
    {
        $this->loop = $loop;
        $this->promise = $promise;
    }

    public function await($timeout = null)
    {
        return await($this->promise, $this->loop, $timeout);
    }

    public function unwrap(): PromiseInterface
    {
        return $this->promise;
    }

    public function then(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null): AwaitablePromise
    {
        return new AwaitablePromise($this->promise->then($onFulfilled, $onRejected, $onProgress), $this->loop);
    }
}