<?php

declare(strict_types=1);

namespace McMatters\Ticl\Traits;

use CurlHandle;

use function array_filter;
use function array_key_exists;
use function array_pop;
use function count;
use function curl_getinfo;
use function explode;
use function is_callable;
use function json_decode;
use function preg_match;
use function substr;
use function trim;

use const JSON_THROW_ON_ERROR;
use const true;

trait ResponsableTrait
{
    protected array $info = [];

    protected int $headerSize;

    protected string $body;

    public function json(
        bool $associative = true,
        int $depth = 512,
        int $flags = JSON_THROW_ON_ERROR
    ): array|object {
        return json_decode(
            $this->body ?? $this->message ?? '',
            $associative,
            $depth,
            $flags,
        );
    }

    public function getInfo(): array
    {
        return $this->info;
    }

    public function getInfoByKey(string $key, $default = null)
    {
        if (!array_key_exists($key, $this->info)) {
            return is_callable($default) ? $default() : $default;
        }

        return $this->info[$key];
    }

    public function getBody(): string
    {
        return $this->body;
    }

    protected function setInfo(CurlHandle $curl): self
    {
        $this->info = curl_getinfo($curl);

        return $this;
    }

    protected function setHeaderSize(): self
    {
        $this->headerSize = $this->getInfoByKey('header_size');

        return $this;
    }

    protected function setHeaders(string $response): self
    {
        $headers = $this->parseHeaders($response, $this->headerSize);

        $this->headers = $headers['headers'] ?? [];
        $this->code = $headers['code'] ?? $this->code;

        return $this;
    }

    protected function parseHeaders(string $response, int $headerSize): array
    {
        if ('' === $response) {
            return [];
        }

        $responseHeaders = array_filter(
            explode("\r\n\r\n", substr($response, 0, $headerSize)),
        );

        if (count($responseHeaders) === 0) {
            return [];
        }

        $headers = [];

        foreach (explode("\r\n", array_pop($responseHeaders)) as $header) {
            $header = trim($header);

            if (!$header) {
                continue;
            }

            $values = explode(': ', $header, 2);

            if (count($values) !== 2) {
                if (preg_match('/^HTTP\/\d(?:\.\d)? (?<code>\d{3})(?:[a-zA-Z\s])*$/', $header, $match)) {
                    $headers['code'] = (int) $match['code'];
                }

                continue;
            }

            [$name, $value] = $values;

            $headers['headers'][$name] = $value;
        }

        return $headers;
    }

    protected function parseBody(string $response, int $headerSize): string
    {
        if ('' === $response) {
            return '';
        }

        return substr($response, $headerSize);
    }
}
