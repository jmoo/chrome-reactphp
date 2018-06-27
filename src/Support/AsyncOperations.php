<?php

namespace Jmoo\React\Support;

use function Clue\React\Block\awaitAll;
use function Clue\React\Block\awaitAny;
use React\EventLoop\LoopInterface;

trait AsyncOperations
{
    protected abstract function getLoop(): LoopInterface;

    public function awaitAll(array $promises)
    {
        return awaitAll($promises, $this->getLoop());
    }

    public function awaitAny(array $promises)
    {
        return awaitAny($promises, $this->getLoop());
    }

    public function sleep($seconds): void
    {
        \Clue\React\Block\sleep($seconds, $this->getLoop());
    }
}