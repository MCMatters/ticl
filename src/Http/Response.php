<?php

declare(strict_types=1);

namespace McMatters\Ticl\Http;

use McMatters\Ticl\Helpers\JsonHelper;
use McMatters\Ticl\Traits\HeadersTrait;
use McMatters\Ticl\Traits\ResponsableTrait;

/**
 * Class Response
 *
 * @package McMatters\Ticl\Http
 */
class Response
{
    use HeadersTrait;
    use ResponsableTrait;

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var string
     */
    protected $body;

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var int
     */
    protected $headerSize;

    /**
     * Response constructor.
     *
     * @param resource $curl
     * @param string $response
     */
    public function __construct($curl, string $response)
    {
        $this->setStatusCodeFromCurlInfo($curl)
            ->setHeaderSize($curl)
            ->setHeaders($response)
            ->setBody($response);
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param bool $associative
     * @param int $depth
     * @param int $options
     *
     * @return mixed
     *
     * @throws \McMatters\Ticl\Exceptions\JsonDecodingException
     */
    public function json(
        bool $associative = true,
        int $depth = 512,
        int $options = 0
    ) {
        return JsonHelper::decode($this->body, $associative, $depth, $options);
    }

    /**
     * @param resource $curl
     *
     * @return self
     */
    protected function setStatusCodeFromCurlInfo($curl): self
    {
        $this->statusCode = $this->parseStatusCodeFromCurlInfo($curl);

        return $this;
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
        $this->statusCode = $headers['code'] ?? $this->statusCode;

        return $this;
    }

    /**
     * @param int|null $code
     *
     * @return self
     */
    protected function setStatusCode(int $code): self
    {
        $this->statusCode = $code;

        return $this;
    }

    /**
     * @param string $response
     *
     * @return self
     */
    protected function setBody(string $response): self
    {
        $this->body = $this->parseBody($response, $this->headerSize);

        return $this;
    }
}
