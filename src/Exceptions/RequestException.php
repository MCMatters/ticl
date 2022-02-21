<?php

declare(strict_types=1);

namespace McMatters\Ticl\Exceptions;

use McMatters\Ticl\Enums\HttpStatusCode;
use McMatters\Ticl\Helpers\JsonHelper;
use McMatters\Ticl\Traits\HeadersTrait;
use McMatters\Ticl\Traits\ResponsableTrait;
use RuntimeException;
use Throwable;

use function curl_errno;
use function get_defined_constants;
use function strpos;
use function strtolower;
use function str_replace;
use function substr;
use function ucfirst;

use const true;

/**
 * Class RequestException
 *
 * @package McMatters\Ticl\Exceptions
 */
class RequestException extends RuntimeException
{
    use HeadersTrait;
    use ResponsableTrait;

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var int
     */
    protected $headerSize;

    /**
     * RequestException constructor.
     *
     * @param string $response
     * @param resource $curl
     */
    public function __construct(string $response, $curl)
    {
        $this->setCode($response, $curl)
            ->setHeaderSize($curl)
            ->setHeaders($response)
            ->setMessage($response, $curl);

        parent::__construct($this->message, $this->code);
    }

    /**
     * @return array
     */
    public function asJson(): array
    {
        try {
            return JsonHelper::decode($this->message);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param resource $curl
     *
     * @return self
     */
    protected function setHeaderSize($curl): self
    {
        $this->headerSize = $this->parseHeaderSize($curl);

        return $this;
    }

    /**
     * @param string $response
     *
     * @return self
     */
    protected function setHeaders(string $response): self
    {
        $headers = $this->parseHeaders($response, $this->headerSize);

        $this->headers = $headers['headers'] ?? [];
        $this->code = $headers['code'] ?? $this->code;

        return $this;
    }

    /**
     * @param string $response
     * @param resource $curl
     *
     * @return self
     */
    protected function setMessage(string $response, $curl): self
    {
        $this->message = '' === $response
            ? $this->getCurlErrorMessage(curl_errno($curl))
            : $this->parseBody($response, $this->headerSize);

        return $this;
    }

    /**
     * @param string $response
     * @param resource $curl
     *
     * @return self
     */
    protected function setCode(string $response, $curl): self
    {
        $this->code = '' === $response
            ? HttpStatusCode::INTERNAL_SERVER_ERROR
            : $this->parseStatusCodeFromCurlInfo($curl);

        return $this;
    }

    /**
     * @param int $code
     *
     * @return string
     */
    protected function getCurlErrorMessage(int $code): string
    {
        $constants = get_defined_constants(true);

        $curlConstants = [];

        foreach ($constants['curl'] ?? [] as $key => $value) {
            if (strpos($key, 'CURLE_') === 0) {
                $curlConstants[(int) $value] = ucfirst(
                    strtolower(str_replace('_', ' ', substr($key, 6)))
                );
            }
        }

        if (!isset($curlConstants[$code])) {
            return 'Internal Server Error';
        }

        return "cURL error {$code}: $curlConstants[$code]";
    }
}
