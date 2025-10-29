<?php

declare(strict_types=1);

namespace McMatters\Ticl\Tests;

use McMatters\Ticl\Client;
use McMatters\Ticl\Enums\HttpStatusCode;
use McMatters\Ticl\Exceptions\RequestException;
use McMatters\Ticl\Http\Response;
use PHPUnit\Framework\TestCase;

class TiclTest extends TestCase
{
    public function testGet(): void
    {
        $client = new Client();

        $response = $client->get('https://www.google.com');

        $this->assertNotEmpty($response->getBody());
        $this->assertSame(HttpStatusCode::OK, $response->getCode());
    }

    public function testGetWithConstructorOptions(): void
    {
        $client = new Client([
            'base_uri' => 'https://www.google.com',
        ]);

        $response = $client->get('/');

        $this->assertNotEmpty($response->getBody());
        $this->assertSame(HttpStatusCode::OK, $response->getCode());
    }

    public function testGetWithGlobalOptions(): void
    {
        Client::setGlobalConfig('base_uri', 'https://www.google.com');

        $client = new Client();

        $response = $client->get('/');

        $this->assertNotEmpty($response->getBody());
        $this->assertSame(HttpStatusCode::OK, $response->getCode());
    }

    public function testAfterCallback(): void
    {
        Client::setGlobalConfig(
            'after_callback',
            function (
                string $method,
                string $url,
                array $headers,
                string $body,
                string $bodyType,
                RequestException|Response $response,
            ) {
                $this->assertSame(HttpStatusCode::OK, $response->getCode());
                $this->assertSame('get', $method);
                $this->assertSame('https://www.google.com', $url);
                $this->assertSame('', $body);
                $this->assertSame('none', $bodyType);
            }
        );

        (new Client())->get('https://www.google.com');
    }

    public function testRetry(): void
    {
        $attempts = 0;
        $retryCount = 5;

        $client = new Client([
            'base_uri' => 'https://google.com/404',
            'retry_count' => $retryCount,
            'after_callback' => function () use (&$attempts) {
                $attempts++;
            },
        ]);

        $this->expectException(RequestException::class);

        $client->get('/');

        $this->assertSame($retryCount, $attempts);
    }

    protected function tearDown(): void
    {
        Client::clearGlobalConfig();
    }
}
