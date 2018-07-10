<?php

namespace Jmoo\React\Chrome;

use React\EventLoop\LoopInterface;

interface DomainInterface
{
    public function enable();

    public function on($event, callable $callable): self;

    public function send($method, $args = []);

    public function getLoop(): LoopInterface;

    public function __call($method, $arguments);
}