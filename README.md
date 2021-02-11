## Tiny Http Client

### Installation

```bash
composer require mcmatters/ticl
```

### Usage

```php
<?php

declare(strict_types=1);

require 'vendor/autoload.php';

$client = new \McMatters\Ticl\Client();

try {
    $response = $client->get('http://example.com/api/user?token=test');
    $user = $response->json();
} catch (\McMatters\Ticl\Exceptions\RequestException $e) {
    $error = $e->asJson();
} catch (\Throwable $e) {
    $error = $e->getMessage();
}
```

### Note

If you want something more customizable, then please use [Guzzle](https://github.com/guzzle/guzzle)
