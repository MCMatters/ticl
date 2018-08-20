<?php

declare(strict_types = 1);

namespace McMatters\Ticl\Exceptions;

use McMatters\Ticl\Enums\HttpStatusCode;
use McMatters\Ticl\Helpers\JsonHelper;
use RuntimeException;
use Throwable;
use const CURLINFO_HEADER_SIZE;
use const true;
use function curl_errno, curl_getinfo, get_defined_constants, strpos, strtolower,
    str_replace, substr, ucfirst;

/**
 * Class RequestException
 *
 * @package McMatters\Ticl\Exceptions
 */
class RequestException extends RuntimeException
{
    /**
     * RequestException constructor.
     *
     * @param string $response
     * @param resource $curl
     */
    public function __construct(string $response, $curl)
    {
        $this->setMessage($response, $curl)->setCode($response, $curl);

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
     * @param string $response
     * @param resource $curl
     *
     * @return self
     */
    protected function setMessage(string $response, $curl): self
    {
        if ('' === $response) {
            $this->message = $this->getCurlErrorMessage(curl_errno($curl));

            return $this;
        }

        $this->message = substr(
            $response,
            (curl_getinfo($curl, CURLINFO_HEADER_SIZE) ?: 0)
        );

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
            : curl_getinfo($curl, CURLINFO_HTTP_CODE);

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
