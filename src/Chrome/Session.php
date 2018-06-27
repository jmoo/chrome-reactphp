<?php

namespace Jmoo\React\Chrome;

use Jmoo\React\Support\AsyncOperations;
use Jmoo\React\Support\AwaitablePromise;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;

class Session implements ConnectionInterface
{
    use AsyncOperations;

    /**
     * @var mixed
     */
    private $sessionId;

    /**
     * @var int
     */
    private $messageId = 0;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var array
     */
    private $domains = [];

    /**
     * @var LoopInterface
     */
    private $loop;

    public function __construct(ConnectionInterface $connection, LoopInterface $loop, $sessionId)
    {
        $this->connection = $connection;
        $this->sessionId = $sessionId;
        $this->loop = $loop;
    }

    public function send($method, $params = []) : AwaitablePromise
    {
        return $this->connection
            ->send('Target.sendMessageToTarget', [
                'sessionId' => $this->sessionId,
                'message' => json_encode([
                    'id' => $this->messageId++,
                    'method' => $method,
                    'params' => $params
                ])
            ])
            ->then(function ($response) {
                return $response->message;
            });
    }

    public function on($event, callable $listener)
    {
        return $this->connection->on($event, function ($response) use ($listener) {
            if ($response->sessionId && $response->sessionId === $this->sessionId) {
                $listener($response->message);
            }
        });
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

    public function getDomain($name): Domain
    {
        return array_key_exists($name, $this->domains)
            ? $this->domains[$name]
            : $this->domains[$name] = new Domain($this, $name);
    }

    public function detach()
    {
        return $this->connection->send('Target.detachFromTarget', ['sessionId' => $this->sessionId]);
    }

    public function once($event, callable $listener)
    {
        return $this->connection->once($event, $listener);
    }

    public function removeListener($event, callable $listener)
    {
        return $this->connection->removeListener($event, $listener);
    }

    public function removeAllListeners($event = null)
    {
        return $this->connection->removeAllListeners($event);
    }

    public function listeners($event = null)
    {
        return $this->connection->listeners($event);
    }

    public function emit($event, array $arguments = [])
    {
        return $this->connection->emit($event, $arguments);
    }

    public function disconnect(): void
    {
        $this->connection->disconnect();
    }

    protected function getLoop()
    {
        return $this->loop;
    }

    public function __get($name): Domain
    {
        return $this->getDomain($name);
    }

    /**
     * @internal
     */
    public function createSession($targetId): AwaitablePromise
    {
        throw new \RuntimeException('Sessions can not be created recursively');
    }
}