<?php

namespace Jmoo\React\Chrome;

use Jmoo\React\Support\AwaitablePromise;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;

class Session extends AbstractConnection
{
    /**
     * @var mixed
     */
    private $sessionId;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    public function __construct(ConnectionInterface $connection, LoopInterface $loop, $sessionId)
    {
        parent::__construct($loop);
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

    public function send($method, $params = []): AwaitablePromise
    {
        $args = new \stdClass;
        $args->id = $this->messageId++;
        $args->method = $method;
        $args->params = $params;

        return $this->connection
            ->send('Target.sendMessageToTarget', [
                'sessionId' => $this->sessionId,
                'message' => json_encode($args)
            ])
            ->then(function () use ($args) {
                return new Promise(function ($resolve, $err) use ($args) {
                    $this->messages[$args->id] = [$resolve, $err];
                });
            });
    }

    public function disconnect()
    {
        $this->connection->send('Target.detachFromTarget');
    }
}