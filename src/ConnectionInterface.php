<?php

namespace Jmoo\React\Chrome;

use Evenement\EventEmitterInterface;
use React\EventLoop\LoopInterface;

interface ConnectionInterface extends EventEmitterInterface
{
    public function send(string $method, array $params = []);

    public function enable(array $domains);

    public function createSession($targetId);

    public function disconnect();

    public function getDomain(string $name);

    public function getLoop(): LoopInterface;

    public function __get($name);
}