<?php

namespace Jmoo\React\Chrome;

use Jmoo\React\Support\AwaitablePromise;
use Evenement\EventEmitterInterface;

interface ConnectionInterface extends EventEmitterInterface
{
    public function send($method, $params = []) : AwaitablePromise;
    public function enable(array $domains) : AwaitablePromise;
    public function disconnect();
    public function awaitAll(array $promises);
    public function awaitAny(array $promises);
    public function sleep($seconds);
    public function getDomain($name) : Domain;
    public function __get($name): Domain;
}