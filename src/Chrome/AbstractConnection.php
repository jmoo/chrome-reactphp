<?php

namespace Jmoo\React\Chrome;

use Evenement\EventEmitter;
use Jmoo\React\Chrome\Exception\ProtocolErrorException;
use Jmoo\React\Support\AsyncOperations;
use Jmoo\React\Support\AwaitablePromise;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;

abstract class AbstractConnection extends EventEmitter implements ConnectionInterface
{
    use AsyncOperations;

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
    }

    public function enable(array $domains): AwaitablePromise
    {
        $out = [];

        $promise = new Promise(function ($resolve, $err) use ($domains, &$out) {
            $resolver = function ($index, $domain) use ($domains, &$out, $resolve) {
                $out[$index] = $this->getDomain($domain);

                if (count($out) === count($domains)) {
                    $resolve($out);
                }
            };

            foreach ($domains as $i => $domain) {
                $this
                    ->send($domain . '.enable')
                    ->then(
                        function () use ($i, $domain, $resolver) {
                            return $resolver($i, $domain);
                        },
                        function ($ex) use ($err) {
                            return $err($ex);
                        });
            }

        });

        return new AwaitablePromise($promise, $this->loop);
    }

    public function getDomain($name): Domain
    {
        return array_key_exists($name, $this->domains)
            ? $this->domains[$name]
            : $this->domains[$name] = new Domain($this, $name);
    }

    protected function getLoop()
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