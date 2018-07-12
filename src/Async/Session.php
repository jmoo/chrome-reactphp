<?php

namespace Jmoo\React\Chrome\Async;

use React\Promise\PromiseInterface;
use React\Promise\Promise;

class Session extends AbstractConnection
{
    /**
     * @var mixed
     */
    private $sessionId;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection, $sessionId)
    {
        parent::__construct($connection->getLoop());
        $this->connection = $connection;
        $this->sessionId = $sessionId;

        $this->connection
            ->on('close', function () {
                $this->emit('close');
            })
            ->on('error', function ($err) {
                $this->emit('error', [$err]);
            });

        $this->on('message', function ($msg) {
            $this->handleMessage($msg);
        });
    }

    public function send(string $method, array $params = []): PromiseInterface
    {
        $args = new \stdClass;
        $args->id = $this->messageId++;
        $args->method = $method;
        $args->params = $params;

        $promise = new Promise(function ($resolve, $err) use ($args) {
            $this->messages[$args->id] = [$resolve, $err];
        });

        return $this->connection
            ->send('Target.sendMessageToTarget', [
                'sessionId' => $this->sessionId,
                'message' => json_encode($args)
            ])
            ->then(function () use ($promise) {
                return $promise;
            });
    }

    public function disconnect()
    {
        $this->connection->send('Target.detachFromTarget');
    }

    /**
     * @internal
     */
    public function createSession($targetId)
    {
        throw new \RuntimeException('Sessions cannot be initialized recursively.');
    }
}
