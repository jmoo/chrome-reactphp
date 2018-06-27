# jmoo/chrome-react
``` composer require jmoo/chrome-react ```

Fully async, low-level client for the Chrome DevTools Protocol using ReactPHP

*Warning: Experimental! Expect large breaking changes, instability, and lack of documentation until there is a tagged version*


### Quickstart
```bash
$ chrome --headless --disable-gpu --remote-debugging-port=9222
```

```php
$sites = ['https://www.chromium.org/', 'https://github.com', 'https://google.com'];
$loop = \React\EventLoop\Factory::create();
$client = new Client($loop);

foreach ($sites as $url) {
    $client
        ->new()
        ->then(function ($page) use ($client) {
            return $client->connect($page->webSocketDebuggerUrl);
        })
        ->then(function ($c) use ($url) {
            $c->Page->enable()->await();
            $c->Page->navigate(['url' => $url])->await();
            $c->disconnect();
        });
}

$loop->run();
```

### Configuration
```php
# Default options
$client = (new Client)->withOptions([
    'host' => '127.0.0.1',
    'port' => 9222,
    'ssl' => false,
    'subProtocols' => [],
    'connectionHeaders' => [], // sent with websocket handshake
    'ws_proxy' => null // ['host' => 'proxy.example.com', 'port' => 443, 'ssl' => true]
]);

```

## Usage
```php
// create new page with http api endpoint
$page = $client->new()->await(); 

// connect to websocket
$c = $client->connect($page->webSocketDebuggerUrl)->await();

# using magic methods
$domain = $c->Page;
$domain->enable()->await();
$domain->navigate(['url' => 'https://google.com']);
$domain->on('domContentEventFired', function($event) {});

# without magic methods
$c->send('Page.enable')->await();
$c->send('Page.navigate', ['url' => 'https://google.com']);
$c->on('Page.domContentEventFired', function($event) {});

# enable multiple domains
list($page, $network, $log) = $c->enable(['Page', 'Network', 'Log']);

```