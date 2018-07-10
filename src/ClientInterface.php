<?php

namespace Jmoo\React\Chrome;

use Evenement\EventEmitterInterface;
use Psr\Http\Message\RequestInterface;
use React\EventLoop\LoopInterface;

interface ClientInterface extends EventEmitterInterface
{
    const DEFAULT_TIMEOUT = 30;

    public function withOptions(array $options = []);

    public function new();

    public function list();

    public function version();

    public function activate($pageId);

    public function close($pageId);

    public function getLoop(): LoopInterface;

    public function connect($url);

    public function send(RequestInterface $request);

    public function request(string $method, string $endpoint, array $headers = [], $body = '');
}