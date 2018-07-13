<?php

declare(strict_types = 1);

namespace McMatters\Ticl\Exceptions;

use RuntimeException;
use const CURLINFO_HEADER_SIZE;
use function curl_getinfo, substr;

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
        $this->setMessage($response, $curl)->setCode($curl);

        parent::__construct($this->message, $this->code);
    }

    /**
     * @param string $response
     * @param resource $curl
     *
     * @return self
     */
    protected function setMessage(string $response, $curl): self
    {
        $this->message = substr(
            $response,
            (curl_getinfo($curl, CURLINFO_HEADER_SIZE) ?: 0)
        );

        return $this;
    }

    /**
     * @param resource $curl
     *
     * @return self
     */
    protected function setCode($curl): self
    {
        $this->code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        return $this;
    }
}
