<?php

namespace Jmoo\React\Chrome\Async;

use Evenement\EventEmitter;
use Clue\React\Buzz\Browser;
use Jmoo\React\Chrome\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use React\Socket\Connector;
use React\Socket\ConnectorInterface;
use RingCentral\Psr7\Response;
use function RingCentral\Psr7\stream_for;

class Client extends EventEmitter implements ClientInterface
{
    /**
     * @var Browser
     */
    private $browser;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var Connector
     */
    private $connector;

    /**
     * @var string
     */
    private $base;

    /**
     * @var array
     */
    private $options = [
        'host' => '127.0.0.1',
        'port' => 9222,
        'ssl' => false,
        'subProtocols' => [],
        'connectionHeaders' => [],
        'ws_proxy' => null
    ];

    public function __construct(LoopInterface $loop = null, ConnectorInterface $connector = null)
    {
        $this->loop = $loop ?: Factory::create();
        $this->connector = $connector ?: new Connector($this->loop);
        $this->browser = new Browser($this->loop, $this->connector);
        $this->applyOptions();
    }

    public function withOptions(array $options = [])
    {
        $client = clone $this;
        $client->applyOptions($options);
        return $client;
    }

    public function new(): PromiseInterface
    {
        return $this->request('POST', '/json/new');
    }

    public function list(): PromiseInterface
    {
        return $this->request('GET', '/json');
    }

    public function version(): PromiseInterface
    {
        return $this->request('GET', '/json/version');
    }

    public function activate($pageId): PromiseInterface
    {
        return $this->request('POST', '/json/activate/' . $pageId);
    }

    public function close($pageId): PromiseInterface
    {
        return $this->request('POST', '/json/close/' . $pageId);
    }

    public function getLoop(): LoopInterface
    {
        return $this->loop;
    }

    public function connect($url): PromiseInterface
    {
        if (!empty($this->options['ws_proxy'])) {
            $url = $this->getWebsocketProxyUrl($url);
        }

        $connector = new \Ratchet\Client\Connector($this->loop, $this->connector);

        return $connector($url, $this->options['subProtocols'], $this->options['connectionHeaders'])
            ->then(function ($socket) use ($url) {
                return new Connection($socket, $this->loop);
            });
    }

    public function request(string $method, string $endpoint, array $headers = [], $body = ''): PromiseInterface
    {
        $request = new \RingCentral\Psr7\Request($method, $endpoint, $headers, stream_for($body), '1.1');
        return $this->send($request);
    }

    public function send(RequestInterface $request)
    {
        $this->emit('request', [$request]);

        return new Promise(function ($resolve, $reject) use ($request) {
            $this->browser
                ->send($request)
                ->then(function (ResponseInterface $response) use ($resolve, $reject) {
                    /* @var $body \React\Stream\ReadableStreamInterface */
                    $body = $response->getBody();
                    $totalBytes = intval($response->getHeaderLine('content-length'));
                    $downloadedBytes = 0;
                    $data = '';

                    $body->on('data', function ($chunk) use ($body, &$data, &$downloadedBytes, $totalBytes) {
                        $downloadedBytes += strlen($chunk);
                        $data .= $chunk;

                        if ($downloadedBytes >= $totalBytes) {
                            $body->close();
                        }
                    });

                    $body->on('error', function (\Exception $error) use ($reject) {
                        $reject($error);
                    });

                    $body->on('close', function () use ($resolve, $reject, $response, &$data, &$downloadedBytes, $totalBytes) {
                        if ($downloadedBytes !== $totalBytes) {
                            $reject(new \RuntimeException("Chrome response error: Expected $totalBytes bytes got $downloadedBytes bytes."));
                        } else {
                            $this->emit('response', [
                                new Response($response->getStatusCode(), $response->getHeaders(), $data)
                            ]);

                            $resolve(json_decode($data));
                        }
                    });
                }, $reject);
        });
    }

    private function getWebsocketProxyUrl($url): string
    {
        $oldBase = 'ws://' . $this->options['host'] . ':' . $this->options['port'];

        $config = $this->options['ws_proxy'];
        $host = array_key_exists('host', $config) ? $config['host'] : $this->options['host'];
        $port = array_key_exists('port', $config) ? $config['port'] : $this->options['port'];
        $ssl = array_key_exists('ssl', $config) ? $config['ssl'] : $this->options['ssl'];

        return substr_replace($url, "ws" . ($ssl ? 's' : '') . "://$host:$port", 0, strlen($oldBase));
    }

    private function applyOptions(array $options = []): void
    {
        $this->options = $options + $this->options;
        $this->base = $this->options['host'] . ':' . $this->options['port'];
        $this->browser = $this->browser
            ->withBase("http" . ($this->options['ssl'] ? 's' : '') . "://" . $this->base)
            ->withOptions(['streaming' => true]);
    }
}