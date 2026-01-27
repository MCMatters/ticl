<?php

declare(strict_types=1);

namespace McMatters\Ticl\Tests;

use McMatters\Ticl\Client;
use McMatters\Ticl\Enums\HttpStatusCode;
use McMatters\Ticl\Exceptions\RequestException;
use McMatters\Ticl\Http\Response;
use PHPUnit\Framework\TestCase;

use function curl_file_create;
use function file_exists;
use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

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

    public function testFileUpload(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'ticl');
        file_put_contents($path, 'test content');

        $client = new Client();

        try {
            $response = $client
                ->withFiles([
                    'file' => curl_file_create($path, 'text/plain', 'test.txt'),
                    'foo' => 'bar',
                ])
                ->post('https://httpbin.org/post');

            $this->assertSame(HttpStatusCode::OK, $response->getCode());
            $json = $response->json();
            $this->assertArrayHasKey('files', $json);
            $this->assertArrayHasKey('file', $json['files']);
            $this->assertSame('bar', $json['form']['foo'] ?? null);
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }
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
