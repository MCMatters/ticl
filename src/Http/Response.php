<?php

declare(strict_types = 1);

namespace McMatters\Ticl\Http;

use McMatters\Ticl\Helpers\JsonHelper;
use McMatters\Ticl\Traits\HeadersTrait;
use const CURLINFO_HEADER_SIZE;
use function array_filter, array_pop, count, curl_getinfo, explode, preg_match, substr, trim;

/**
 * Class Response
 *
 * @package McMatters\Ticl\Http
 */
class Response
{
    use HeadersTrait;

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
        $this->setHeaderSize($curl)
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
    protected function setHeaderSize($curl): self
    {
        $this->headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE) ?: 0;

        return $this;
    }

    /**
     * @param string $response
     *
     * @return self
     */
    protected function setHeaders(string $response): self
    {
        $headers = substr($response, 0, $this->headerSize);

        $responseHeaders = array_filter(explode("\r\n\r\n", $headers));

        foreach (explode("\r\n", array_pop($responseHeaders)) as $header) {
            $header = trim($header);

            if (!$header) {
                continue;
            }

            $values = explode(': ', $header, 2);

            if (count($values) !== 2) {
                if (preg_match('/^HTTP\/\d\.\d (?<code>\d{3}) [a-zA-Z\s]+$/', $header, $match)) {
                    $this->setStatusCode((int) $match['code']);
                }

                continue;
            }

            list($name, $value) = $values;

            $this->headers[$name] = $value;
        }

        return $this;
    }

    /**
     * @param int $code
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
        $this->body = substr($response, $this->headerSize);

        return $this;
    }
}
