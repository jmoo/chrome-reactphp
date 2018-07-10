<?php

namespace Jmoo\React\Chrome\Blocking;

use Evenement\EventEmitterInterface;
use Jmoo\React\Chrome\ClientInterface;
use Psr\Http\Message\RequestInterface;
use React\EventLoop\LoopInterface;
use Jmoo\React\Chrome\Async\Client as AsyncClient;

class Client implements ClientInterface
{
    use EventHandlerTrait;

    /**
     * @var AsyncClient
     */
    private $client;

    public function __construct(AsyncClient $client = null)
    {
        $this->client = $client ?: new AsyncClient;
    }

    public function withOptions(array $options = [])
    {
        $clone = clone $this;
        $clone->client = $clone->client->withOptions($options);

        if (array_key_exists('timeout', $options)) {
            $this->timeout = $options['timeout'];
        }

        return $clone;
    }

    public function new(int $timeout = null)
    {
        return $this->block($this->client->new(), $timeout);
    }

    public function list(int $timeout = null)
    {
        return $this->block($this->client->list(), $timeout);
    }

    public function version(int $timeout = null)
    {
        return $this->block($this->client->version(), $timeout);
    }

    public function activate($pageId, int $timeout = null)
    {
        return $this->block($this->client->activate($pageId), $timeout);
    }

    public function close($pageId, int $timeout = null)
    {
        return $this->block($this->client->close($pageId), $timeout);
    }

    public function getLoop(): LoopInterface
    {
        return $this->client->getLoop();
    }

    public function connect($url, int $timeout = null)
    {
        return new Connection($this->block($this->client->connect($url), $timeout), $this->timeout);
    }

    public function send(RequestInterface $request, int $timeout = null)
    {
        return $this->block($this->client->send($request), $timeout);
    }

    public function request(string $method, string $endpoint, array $headers = [], $body = '', int $timeout = null)
    {
        return $this->block($this->client->request($method, $endpoint, $headers, $body), $timeout);
    }

    protected function getEmitter(): EventEmitterInterface
    {
        return $this->client;
    }
}