<?php

namespace Jmoo\React\Chrome\Async;

use Evenement\EventEmitter;
use Jmoo\React\Chrome\ConnectionInterface;
use Jmoo\React\Chrome\Exception\ProtocolErrorException;
use React\EventLoop\LoopInterface;
use function React\Promise\all;
use React\Promise\PromiseInterface;

abstract class AbstractConnection extends EventEmitter implements ConnectionInterface
{
    /**
     * @var int
     */
    protected $messageId = 0;

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @var array
     */
    protected $domains = [];

    /**
     * @var LoopInterface
     */
    protected $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;

        $this->on('close', function () {
            $ex = new \RuntimeException('Connection terminated');
            foreach ($this->messages as $definition) {
                list(, $reject) = $definition;
                $reject($ex);
            }
        });
    }

    public function enable(array $domains): PromiseInterface
    {
        $promises = [];

        foreach ($domains as $domain) {
            $promises[] = $this->send($domain . '.enable');
        }

        return all($promises)->then(function () use ($domains) {
            $out = [];

            foreach ($domains as $domain) {
                $out[] = $this->getDomain($domain);
            }

            return $out;
        });
    }

    public function getDomain($name): Domain
    {
        return array_key_exists($name, $this->domains)
            ? $this->domains[$name]
            : $this->domains[$name] = new Domain($this, $name);
    }

    public function getLoop(): LoopInterface
    {
        return $this->loop;
    }

    protected function handleMessage($msg)
    {
        if (!($data = json_decode($msg)) || !is_object($data)) {
            throw new \RuntimeException('Unable to parse message: ' . $msg);
        }

        $this->emit('receive', [$data]);

        if (property_exists($data, 'method')) {
            $this->emit($data->method, [$data->params]);

        } else if (property_exists($data, 'id')) {
            $this->handleResponse($data);

        } else if (property_exists($data, 'error')) {
            $this->handleProtocolError($data);
        }
    }

    protected function handleResponse(\stdClass $data): void
    {
        if (!empty($this->messages[$data->id])) {
            list($resolve, $err) = $this->messages[$data->id];

            if (empty($data)) {
                $this->emit('error', [$ex = new \RuntimeException('Empty response')]);
                $err($ex);
                return;
            }

            if (!empty($data->error)) {
                $this->handleProtocolError($data, $err);
                return;
            }

            $resolve($data->result);
            unset($this->messages[$data->id]);
        }
    }

    protected function handleProtocolError(\stdClass $data, callable $handler = null): void
    {
        $details = !empty($data->error->message) ? $data->error->message : 'Unknown Protocol Exception';
        $ex = new ProtocolErrorException($details, $data->error->code);

        $this->emit('error', [$ex]);

        if ($handler) {
            $handler($ex);
        }
    }

    public function __get($name): Domain
    {
        return $this->getDomain($name);
    }
}