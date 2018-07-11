# jmoo/chrome-react


[![Build Status](https://travis-ci.com/jmoo/chrome-reactphp.svg?branch=master)](https://travis-ci.com/jmoo/chrome-reactphp)

[DevTools Api](https://chromedevtools.github.io/devtools-protocol/tot) 

Fully async, low-level client for the Chrome DevTools Protocol using ReactPHP


*Warning: Experimental! Expect large breaking changes, instability, and lack of documentation until there is a tagged version*

### Installation

```bash
$ composer require jmoo/chrome-react
```

### Getting Started
#### Running Chrome
```bash
$ chrome --headless --disable-gpu --remote-debugging-port=9222
```

#### Navigate to a page (synchronously)
The blocking client runs the event loop while awaiting completion of each task.
This allows you to write normal synchronous code while still responding to asynchronous events.
```php
$chrome = new \Jmoo\React\Chrome\Blocking\Client;
$url = $chrome->new()->webSocketDebuggerUrl;
$tab = $chrome->connect($url);

$tab->Page->enable();
$tab->Page->navigate(['url' => 'https://www.chromium.org/']);
$tab->disconnect();
```

#### Navigate to a page (asynchronously)
The async client returns Promises for each command.
```php
$loop = \React\EventLoop\Factory::create();
$chrome = new \Jmoo\React\Chrome\Async\Client($loop);

$chrome
    ->new()
    ->then(function ($page) use ($chrome) {
        return $chrome->connect($page->webSocketDebuggerUrl);
    })
    ->then(function ($c) {
        return \React\Promise\all([
            $c,
            $c->Page->enable(),
            $c->Page->navigate(['url' => 'https://www.google.com'])
        ]);
    })
    ->then(function ($result) {
        list($c) = $result;
        $c->disconnect();
    });
    
$loop->run();
```

#### Navigate to a page (coroutines)
The async client can be used with amphp coroutines using ```amphp/react-adapter```
```php
\Amp\Loop::run(function() {
    $chrome = new \Jmoo\React\Chrome\Async\Client(ReactAdapter::get());

    $tabInfo = yield $chrome->new();
    $tab = yield $chrome->connect($tabInfo->webSocketDebuggerUrl);

    yield $tab->Page->enable();
    yield $tab->Page->navigate(['url' => 'https://news.ycombinator.com/']);
    $tab->disconnect();
});
```

#### Using an existing event loop with the blocking client
```php
// any existing event loop
$loop = \React\EventLoop\Factory::create();

// create a new async client with event loop
$async = new \Jmoo\React\Chrome\Async\Client($loop);

// create a new blocking client using async client
$chrome = new \Jmoo\React\Chrome\Blocking\Client($async);

```

### Usage

#### Configuration
```php
# Default options
$client = (new Client)->withOptions([
    'host' => '127.0.0.1',
    'port' => 9222,
    'ssl' => false,
    'timeout' => 30 // blocking client only
]);

# Using a custom event-loop and connector
$asyncClient = new \Jmoo\React\Chrome\Async\Client($loop, $connector);
$blockingClient = new \Jmoo\React\Chrome\Blocking\Client($asyncClient);

```

#### Domains
```php
$client = new \Jmoo\React\Chrome\Blocking\Client;
$c = $client->connect($client->new()->webSocketDebuggerUrl);

// getting a domain accessor
$page = $c->Page;   // with magic method
$page = $c->getDomain('Page'); // directly

// enable events and retrieve multiple domain accessors at the same time
list($page, $network, $log) = $c->enable(['Page', 'Network', 'Log']); 

```

#### Methods
```php
$client = new \Jmoo\React\Chrome\Blocking\Client;
$c = $client->connect($client->new()->webSocketDebuggerUrl);

// executing a method using the domain accessor
$c->Page->navigate(['url' => 'http://jmoo.io']); // with magic method
$c->Page->send('navigate', ['url' => 'http://jmoo.io']); // directly

// without using domain accessor
$c->send('Page.navigate', ['url' => 'http://jmoo.io']); 

```

#### Events
```php
$client = new \Jmoo\React\Chrome\Blocking\Client;
$c = $client->connect($client->new()->webSocketDebuggerUrl);

// events must be enabled
$c->Page->enable();

$c->Page->on('domContentEventFired', function() use ($c) {
    $c->disconnect();
});

// pause execution until disconnect (blocking client only)
$c->waitForDisconnect();

```

#### Sessions
```php
$client = new \Jmoo\React\Chrome\Blocking\Client;
$c = $client->connect($client->version()->webSocketDebuggerUrl);

$target = $c->send('Target.createTarget', ['url' => 'about:blank']);
$session = $c->createSession($target->targetId);
$session->Page->enable();
```