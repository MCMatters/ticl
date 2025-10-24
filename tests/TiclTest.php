<?php

declare(strict_types=1);

namespace McMatters\Ticl\Tests;

use CurlHandle;
use McMatters\Ticl\Client;
use McMatters\Ticl\Enums\HttpStatusCode;
use McMatters\Ticl\Exceptions\RequestException;
use PHPUnit\Framework\TestCase;

use function curl_getinfo;

use const CURLINFO_HTTP_CODE;

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
        Client::setGlobalConfig('after_callback', function (CurlHandle $curl, bool|string $response) {
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            $this->assertSame(HttpStatusCode::OK, $code);
            $this->assertNotEmpty($response);
        });

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
