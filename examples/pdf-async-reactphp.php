<?php

use Jmoo\React\Chrome\Async\Client;
use Jmoo\React\Chrome\Async\Connection;

require __DIR__ . '/../vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();
$client = new Client($loop);

$client
    ->new()
    ->then(function ($page) use ($client) {
        return $client->connect($page->webSocketDebuggerUrl);
    })
    ->then(function (Connection $c) {
        return \React\Promise\all([
            $c,
            $c->Page->enable(),
            $c->Page->navigate(['url' => 'https://www.google.com'])
        ]);
    })
    ->then(function ($result) {
        /** @var Connection $c */
        list($c) = $result;

        $deferred = new \React\Promise\Deferred;

        $c->Page->on('loadEventFired', function () use ($c, $deferred) {
            $c->Page->printToPDF()->then([$deferred, 'resolve'], [$deferred, 'reject']);
        });

        return $deferred->promise()->then(function ($result) use ($c) {
            file_put_contents(sys_get_temp_dir() . '/google.pdf', base64_decode($result->data));
            $c->disconnect();
        });
    });

$loop->run();