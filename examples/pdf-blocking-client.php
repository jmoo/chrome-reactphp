<?php

use Jmoo\React\Chrome\Blocking\Client;

require __DIR__ . '/../vendor/autoload.php';

$chrome = new Client;

$url = $chrome->new()->webSocketDebuggerUrl;
$tab = $chrome->connect($url);

$tab->Page->enable();
$tab->Page->navigate(['url' => 'https://www.chromium.org/']);

$tab->Page->on('loadEventFired', function() use ($tab) {
    $pdf = base64_decode($tab->Page->printToPDF()->data);
    file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'chromium.pdf', $pdf);
    $tab->disconnect();
});

$tab->waitUntilDisconnect();

