<?php

namespace Jmoo\React\Chrome\Async;

use Evenement\EventEmitterInterface;
use Ratchet\Client\WebSocket;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

class Connection extends AbstractConnection
{
    /**
     * @var EventEmitterInterface
     */
    private $socket;

    /**
     * @var array
     */
    private $sessions = [];

    public function __construct(WebSocket $socket, LoopInterface $loop)
    {
        parent::__construct($loop);
        $this->socket = $socket;

        $socket
            ->on('message', function ($msg) {
                $this->handleMessage($msg);
            })
            ->on('error', function ($err) {
                $this->emit('error', [$err]);
            })
            ->on('close', function () {
                $this->emit('close');
            });

        $this
            ->on('Target.receivedMessageFromTarget', function ($event) {
                $this->sendToSession($event->sessionId, 'message', [$event->message]);
            })
            ->on('Target.detachedFromTarget', function ($event) {
                $this->sendToSession($event->sessionId, 'close');
                unset($this->sessions[$event->sessionId]);
            });
    }

    public function send(string $method, array $params = []): PromiseInterface
    {
        $args = new \stdClass;
        $args->id = $this->messageId++;
        $args->method = $method;
        $args->params = $params;

        $this->emit('send', [$args]);

        return new Promise(function ($resolve, $err) use ($args) {
            $this->messages[$args->id] = [$resolve, $err];
            $this->socket->send(json_encode($args));
        });
    }

    public function disconnect()
    {
        $this->socket->close();
    }

    public function createSession($targetId): PromiseInterface
    {
        return $this
            ->send('Target.attachToTarget', ['targetId' => $targetId])
            ->then(function ($response) use ($targetId) {
                return $this->sessions[$response->sessionId] = new Session($this, $response->sessionId);
            });
    }

    private function sendToSession($sessionId, $event, $params = null)
    {
        if (!array_key_exists($sessionId, $this->sessions)) {
            throw new \RuntimeException('Cannot send message to detached session');
        }

        $this->sessions[$sessionId]->emit($event, $params);
    }
}