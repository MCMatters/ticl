<?php

declare(strict_types=1);

namespace McMatters\Ticl\Http;

use CurlHandle;
use McMatters\Ticl\Traits\HeadersTrait;
use McMatters\Ticl\Traits\ResponsableTrait;

class Response
{
    use HeadersTrait;
    use ResponsableTrait;

    protected int $code;

    public function __construct(CurlHandle $curl, string $response)
    {
        $this->setInfo($curl)
            ->setCode()
            ->setHeaderSize()
            ->setHeaders($response)
            ->setBody($response);
    }

    public function getCode(): int
    {
        return $this->code;
    }

    protected function setCode(): self
    {
        $this->code = $this->getInfoByKey('http_code');

        return $this;
    }

    protected function setBody(string $response): self
    {
        $this->body = $this->parseBody($response, $this->headerSize);

        return $this;
    }
}
