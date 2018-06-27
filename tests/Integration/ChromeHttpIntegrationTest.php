<?php

namespace Jmoo\React\Chrome\Tests\Integration;

use Jmoo\React\Chrome\Client;

class ChromeHttpIntegrationTest extends \PHPUnit\Framework\TestCase
{
    /** @test */
    public function canIssueRequestForRemoteDebuggingUrl()
    {
        $client = (new Client)->withOptions(['host' => 'localhost']);
        $version = $client->version()->await();
        $this->assertObjectHasAttribute('webSocketDebuggerUrl', $version);
    }
}