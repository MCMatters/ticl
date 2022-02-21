<?php

declare(strict_types=1);

namespace McMatters\Ticl\Traits;

use function array_filter;
use function array_pop;
use function count;
use function curl_getinfo;
use function explode;
use function preg_match;
use function substr;
use function trim;

use const CURLINFO_HEADER_SIZE;
use const CURLINFO_HTTP_CODE;

/**
 * Class ResponsableTrait
 *
 * @package
 */
trait ResponsableTrait
{
    /**
     * @param resource $curl
     *
     * @return int
     */
    protected function parseHeaderSize($curl): int
    {
        return (int) (curl_getinfo($curl, CURLINFO_HEADER_SIZE) ?: 0);
    }

    /**
     * @param string $response
     * @param int $headerSize
     *
     * @return array
     */
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

            list($name, $value) = $values;

            $headers['headers'][$name] = $value;
        }

        return $headers;
    }

    /**
     * @param resource $curl
     *
     * @return int
     */
    protected function parseStatusCodeFromCurlInfo($curl): int
    {
        return (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    }

    /**
     * @param string $response
     * @param int $headerSize
     *
     * @return string
     */
    protected function parseBody(string $response, int $headerSize): string
    {
        if ('' === $response) {
            return '';
        }

        return substr($response, $headerSize);
    }
}
