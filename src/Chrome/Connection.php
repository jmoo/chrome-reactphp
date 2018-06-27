<?php

namespace Jmoo\React\Chrome;

use Jmoo\React\Chrome\Exception\ProtocolErrorException;
use Evenement\EventEmitter;
use Evenement\EventEmitterInterface;
use Jmoo\React\Support\AsyncOperations;
use Jmoo\React\Support\AwaitablePromise;
use Ratchet\Client\WebSocket;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;

class Connection extends EventEmitter implements ConnectionInterface
{
    use AsyncOperations;

    /**
     * @var EventEmitterInterface
     */
    private $socket;

    /**
     * @var array
     */
    private $messages = [];

    /**
     * @var int
     */
    private $messageId = 0;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var array
     */
    private $domains = [];


    public function __construct(WebSocket $socket, LoopInterface $loop)
    {
        $this->socket = $socket;
        $this->loop = $loop;

        $socket
            ->on('message', function ($msg) {
                if (!($data = json_decode($msg)) || !is_object($data)) {
                    throw new \RuntimeException('Unable to parse message: ' . $msg);
                }

                $this->emit('receive', [$data]);

                if (property_exists($data, 'method')) {
                    $this->handleEvent($data);
                } else if (property_exists($data, 'id')) {
                    $this->handleResponse($data);
                } else if (property_exists($data, 'error')) {
                    $this->handleProtocolError($data);
                }
            })
            ->on('error', function ($err) {
                throw $err;
            })
            ->on('close', function () {
                $this->emit('close');
            });
    }

    public function send($method, $params = []) : AwaitablePromise
    {
        $args = new \stdClass;
        $args->id = $this->messageId++;
        $args->method = $method;
        $args->params = $params;

        $this->emit('send', [$args]);

        return new AwaitablePromise(new Promise(function ($resolve, $err) use ($args) {
            $this->messages[$args->id] = [$resolve, $err];
            $this->socket->send(json_encode($args));
        }), $this->loop);
    }

    public function disconnect() : void
    {
        $this->socket->close();
    }

    public function enable(array $domains): AwaitablePromise
    {
        $out = [];

        $promise = new Promise(function ($resolve) use ($domains, &$out) {
            $resolver = function ($index, $domain) use ($domains, &$out, $resolve) {
                $out[$index] = $this->getDomain($domain);

                if (count($out) === count($domains)) {
                    $resolve($out);
                }
            };

            foreach ($domains as $i => $domain) {
                $this->send($domain . '.enable')->then(function () use ($i, $domain, $resolver) {
                    return $resolver($i, $domain);
                });
            }

        });

        return new AwaitablePromise($promise, $this->loop);
    }

    public function getDomain($name) : Domain
    {
        return array_key_exists($name, $this->domains)
            ? $this->domains[$name]
            : $this->domains[$name] = new Domain($this, $name);
    }

    public function createSession($targetId) : AwaitablePromise
    {
        return $this
            ->send('Target.attachToTarget', ['targetId' => $targetId])
            ->then(function ($sessionId) use ($targetId) {
                return new Session($this, $this->loop, $sessionId);
            });
    }

    protected function getLoop() : LoopInterface
    {
        return $this->loop;
    }

    private function handleEvent(\stdClass $data) : void
    {
        $this->emit($data->method, [$data->params]);
    }

    private function handleResponse(\stdClass $data) : void
    {
        if (!property_exists($data, 'id')) {
            throw new \RuntimeException('Invalid response (message id missing): ' . json_encode($data));
        }

        if (!empty($this->messages[$data->id])) {
            list($resolve, $err) = $this->messages[$data->id];

            if (empty($data)) {
                $this->emit('error', [$ex = new \RuntimeException('Empty response')]);
                $err($ex);
                return;
            }

            if (!empty($data->error)) {

                return;
            }

            $resolve($data->result);
            unset($this->messages[$data->id]);
        }
    }

    private function handleProtocolError(\stdClass $data, callable $handler = null) : void
    {
        $details = !empty($data->error->data) ? $data->error->data : 'Unknown Protocol Exception';
        $ex = new ProtocolErrorException($details, $data->error->code);

        if ($handler) {
            $this->emit('error', [$ex]);
            $handler($ex);
        } else {
            throw $ex;
        }
    }

    public function __get($name): Domain
    {
        return $this->getDomain($name);
    }
}