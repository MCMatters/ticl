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
use function preg_match;
use function substr;
use function trim;

use const CURLINFO_HEADER_SIZE;
use const CURLINFO_HTTP_CODE;

trait ResponsableTrait
{
    protected array $info = [];

    protected array $headers = [];

    protected int $headerSize;

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

    public function getHeaders(): array
    {
        return $this->headers;
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

    protected function parseHeaderSize(CurlHandle $curl): int
    {
        return (int) (curl_getinfo($curl, CURLINFO_HEADER_SIZE) ?: 0);
    }

    protected function parseHeaders(string $response, int $headerSize): array
    {
        if ('' === $response) {
            return [];
        }

        $responseHeaders = array_filter(explode("\r\n\r\n", substr($response, 0, $headerSize)));

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

    protected function parseStatusCodeFromCurlInfo(CurlHandle $curl): int
    {
        return (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    }

    protected function parseBody(string $response, int $headerSize): string
    {
        if ('' === $response) {
            return '';
        }

        return substr($response, $headerSize);
    }
}
