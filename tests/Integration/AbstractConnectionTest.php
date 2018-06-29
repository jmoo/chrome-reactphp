<?php

namespace Jmoo\React\Chrome\Tests\Integration;

use Jmoo\React\Chrome\Client;
use Jmoo\React\Chrome\ConnectionInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractConnectionTest extends \PHPUnit\Framework\TestCase
{
    const TIMEOUT = 5;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var Client
     */
    protected $client;

    protected abstract function connect(): ConnectionInterface;

    protected function setUp($logFile = null)
    {
        $this->log = new Logger(get_class($this));
        $this->log->pushHandler(new StreamHandler($logFile ?: __DIR__ . '/../../chrome.log'));

        $this->client = new Client;
        $this->attachLoggerToClient($this->client);

        $this->connection = $this->connect();
    }

    protected function attachLoggerToClient(Client $client)
    {
        $client
            ->on('request', function (RequestInterface $request) {
                $this->log->debug("-> " . $request->getMethod() . ' ' . $request->getUri() . "\n");
            })
            ->on('response', function (ResponseInterface $response) {
                $body = trim($response->getBody()->getContents());
                $this->log->debug("<- " . $response->getStatusCode() . " $body\n");
            });
    }

    protected function attachLoggerToConnection(ConnectionInterface $connection)
    {
        $connection
            ->on('send', function ($message) {
                $this->log->debug('-> ' . $message->id . ': ' . $message->method . ' - ' . json_encode($message->params) . "\n");
            })
            ->on('receive', function ($response) {
                $this->log->debug('<- ' . json_encode($response) . "\n");
            })
            ->on('error', function (\Exception $ex) {
                $this->log->error($ex->getMessage());
            });
    }

    protected function tearDown()
    {
        $this->connection->disconnect();
    }
}