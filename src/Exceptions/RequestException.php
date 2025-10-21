<?php

declare(strict_types=1);

namespace McMatters\Ticl\Exceptions;

use CurlHandle;
use McMatters\Ticl\Enums\HttpStatusCode;
use McMatters\Ticl\Traits\HeadersTrait;
use McMatters\Ticl\Traits\ResponsableTrait;
use RuntimeException;

use function curl_errno;
use function get_defined_constants;
use function json_decode;
use function mb_strtolower;
use function mb_substr;
use function str_replace;
use function str_starts_with;
use function ucfirst;

use const JSON_THROW_ON_ERROR;
use const true;

class RequestException extends RuntimeException
{
    use HeadersTrait;
    use ResponsableTrait;

    public function __construct(CurlHandle $curl, string $response)
    {
        $this->setCode($curl, $response)
            ->setHeaderSize($curl)
            ->setHeaders($response)
            ->setMessage($curl, $response);

        parent::__construct($this->message, $this->code);
    }

    /**
     * @return array|object
     *
     * @throws \JsonException
     */
    public function asJson(bool $associative = true, int $depth = 512)
    {
        return json_decode($this->message, $associative, $depth, JSON_THROW_ON_ERROR);
    }

    protected function setHeaders(string $response): self
    {
        $headers = $this->parseHeaders($response, $this->headerSize);

        $this->headers = $headers['headers'] ?? [];
        $this->code = $headers['code'] ?? $this->code;

        return $this;
    }

    protected function setMessage(CurlHandle $curl, string $response): self
    {
        $this->message = '' === $response
            ? $this->getCurlErrorMessage(curl_errno($curl))
            : $this->parseBody($response, $this->headerSize);

        return $this;
    }

    protected function setCode(CurlHandle $curl, string $response): self
    {
        $this->code = '' === $response
            ? HttpStatusCode::INTERNAL_SERVER_ERROR
            : $this->parseStatusCodeFromCurlInfo($curl);

        return $this;
    }

    protected function getCurlErrorMessage(int $code): string
    {
        $constants = get_defined_constants(true);

        $curlConstants = [];

        foreach ($constants['curl'] ?? [] as $key => $value) {
            if (str_starts_with($key, 'CURLE_')) {
                $curlConstants[(int) $value] = ucfirst(
                    mb_strtolower(str_replace('_', ' ', mb_substr($key, 6))),
                );
            }
        }

        if (!isset($curlConstants[$code])) {
            return 'Internal Server Error';
        }

        return "cURL error {$code}: $curlConstants[$code]";
    }
}
