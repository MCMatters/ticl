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
use function mb_strtolower;
use function mb_substr;
use function str_replace;
use function str_starts_with;
use function ucfirst;

use const true;

class RequestException extends RuntimeException
{
    use HeadersTrait;
    use ResponsableTrait;

    public function __construct(CurlHandle $curl, string $response)
    {
        $this->setInfo($curl)
            ->setCode($response)
            ->setHeaderSize()
            ->setHeaders($response)
            ->setBody($curl, $response);

        parent::__construct($this->body, $this->code);
    }

    protected function setBody(CurlHandle $curl, string $response): self
    {
        $this->body = '' === $response
            ? $this->getCurlErrorMessage(curl_errno($curl))
            : $this->parseBody($response, $this->headerSize);

        return $this;
    }

    protected function setCode(string $response): self
    {
        $this->code = '' === $response
            ? HttpStatusCode::INTERNAL_SERVER_ERROR
            : $this->getInfoByKey('http_code');

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
