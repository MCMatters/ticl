<?php

declare(strict_types=1);

namespace McMatters\Ticl\Http;

use CurlHandle;
use McMatters\Ticl\Traits\HeadersTrait;
use McMatters\Ticl\Traits\ResponsableTrait;

use function curl_getinfo;
use function json_decode;

use const JSON_THROW_ON_ERROR;
use const true;

class Response
{
    use HeadersTrait;
    use ResponsableTrait;

    protected int $statusCode;

    protected string $body;

    protected array $info = [];

    protected array $headers = [];

    protected int $headerSize;

    public function __construct(CurlHandle $curl, string $response)
    {
        $this->setStatusCodeFromCurlInfo($curl)
            ->setInfo($curl)
            ->setHeaderSize($curl)
            ->setHeaders($response)
            ->setBody($response);
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return array|object
     *
     * @throws \JsonException
     */
    public function json(bool $associative = true, int $depth = 512)
    {
        return json_decode($this->body, $associative, $depth, JSON_THROW_ON_ERROR);
    }

    protected function setStatusCodeFromCurlInfo(CurlHandle $curl): self
    {
        $this->statusCode = $this->parseStatusCodeFromCurlInfo($curl);

        return $this;
    }

    protected function setInfo(CurlHandle $curl): self
    {
        $this->info = curl_getinfo($curl);

        return $this;
    }

    protected function setHeaderSize(CurlHandle $curl): self
    {
        $this->headerSize = $this->parseHeaderSize($curl);

        return $this;
    }

    protected function setHeaders(string $response): self
    {
        $headers = $this->parseHeaders($response, $this->headerSize);

        $this->headers = $headers['headers'] ?? [];
        $this->statusCode = $headers['code'] ?? $this->statusCode;

        return $this;
    }

    protected function setBody(string $response): self
    {
        $this->body = $this->parseBody($response, $this->headerSize);

        return $this;
    }
}
