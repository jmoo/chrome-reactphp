<?php

use Amp\ReactAdapter\ReactAdapter;
use Jmoo\React\Chrome\Async\Client;

require __DIR__ . '/../vendor/autoload.php';

\Amp\Loop::run(function() {
    $chrome = new Client(ReactAdapter::get());

    $tabInfo = yield $chrome->new();
    $tab = yield $chrome->connect($tabInfo->webSocketDebuggerUrl);

    yield $tab->Page->enable();
    yield $tab->Page->navigate(['url' => 'https://news.ycombinator.com/']);

    $deferred = new \Amp\Deferred;
    $tab->Page->on('loadEventFired', function() use ($tab, $deferred) {
        $tab->Page->printToPDF()->then([$deferred, 'resolve'], [$deferred, 'fail']);
    });

    $result = yield $deferred->promise();
    $pdf = base64_decode($result->data);

    file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'hn.pdf', $pdf);
    $tab->disconnect();
});