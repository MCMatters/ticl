<?php

declare(strict_types=1);

namespace McMatters\Ticl\Http;

use CurlHandle;
use McMatters\Ticl\Enums\HttpStatusCode;
use McMatters\Ticl\Exceptions\RequestException;
use McMatters\Ticl\Http\Traits\RequestDataHandlingTrait;
use McMatters\Ticl\Http\Traits\RequestQueryHandlingTrait;
use McMatters\Ticl\Traits\HeadersTrait;

use function array_filter;
use function array_key_exists;
use function curl_close;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_reset;
use function curl_setopt;
use function is_bool;
use function is_callable;
use function method_exists;
use function ucfirst;

use const CURLINFO_HTTP_CODE;
use const CURLOPT_CUSTOMREQUEST;
use const CURLOPT_FAILONERROR;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HEADER;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_MAXREDIRS;
use const CURLOPT_NOBODY;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_URL;
use const false;
use const null;
use const true;

class Request
{
    use HeadersTrait;
    use RequestDataHandlingTrait;
    use RequestQueryHandlingTrait;

    protected ?CurlHandle $curl;

    protected string $uri;

    protected string $method;

    protected ?string $body = null;

    protected ?string $bodyType = null;

    protected array $options = [];

    public function __construct(
        string $method,
        string $uri,
        array $options = [],
    ) {
        $this->curl = curl_init();

        $this->setDefaults($method, $uri, $options);
    }

    public function __destruct()
    {
        if ($this->curl) {
            curl_close($this->curl);

            $this->curl = null;
        }
    }

    public function setDefaults(
        string $method,
        string $uri,
        array $options = [],
    ): self {
        $this->setHeaders($options)
            ->setUri($uri)
            ->setMethod($method)
            ->setOptions($options);

        return $this;
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \McMatters\Ticl\Exceptions\RequestException
     */
    public function send(): Response
    {
        curl_reset($this->curl);

        $this->setCurlOptions();
        $this->setOptionsDependOnMethod();

        $url = $this->getUriForRequest();
        $headers = $this->getHeadersForRequest();

        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->curl, CURLOPT_HEADER, true);

        $this->setUpRedirects();

        try {
            $response = curl_exec($this->curl);

            if (curl_getinfo($this->curl, CURLINFO_HTTP_CODE) >= HttpStatusCode::BAD_REQUEST) {
                $response = new RequestException($this->curl, is_bool($response) ? '' : $response);

                throw $response;
            }

            if (false === $response) {
                $response = new RequestException($this->curl, '');

                throw $response;
            }

            $response = new Response($this->curl, is_bool($response) ? '' : $response);

            return $response;
        } finally {
            $this->callAfterCallback($url, $response);

            $this->body = null;
            $this->bodyType = null;
        }
    }

    protected function setOptionsDependOnMethod(): void
    {
        $method = 'prepare'.ucfirst($this->method).'Request';

        if (method_exists($this, $method)) {
            $this->{$method}();
        }
    }

    protected function setCurlOptions(): void
    {
        foreach ($this->options['curl'] ?? [] as $key => $value) {
            curl_setopt($this->curl, $key, $value);
        }
    }

    protected function prepareHeadRequest(): void
    {
        curl_setopt($this->curl, CURLOPT_NOBODY, true);
        $this->prepareGetRequest();
    }

    protected function prepareGetRequest(): void
    {
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_FAILONERROR, false);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    protected function preparePostRequest(): void
    {
        $this->prepareGetRequest();
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->getBody());
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    protected function preparePutRequest(): void
    {
        $this->preparePostRequest();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    protected function preparePatchRequest(): void
    {
        $this->preparePostRequest();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    protected function prepareDeleteRequest(): void
    {
        $this->preparePostRequest();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    protected function setUpRedirects(): void
    {
        if ($this->options['follow_redirects'] ?? true) {
            curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($this->curl, CURLOPT_MAXREDIRS, $this->options['max_redirects'] ?? 5);
        } else {
            curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, false);
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function getUriForRequest(): string
    {
        if (array_key_exists('query', $this->options)) {
            return $this->handleQueryRequest();
        }

        return $this->uri;
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \JsonException
     */
    protected function getBody(): string
    {
        if (null !== $this->body) {
            return $this->body;
        }

        if (array_key_exists('json', $this->options)) {
            $this->body = $this->handleJsonRequestData();
            $this->bodyType = 'json';
        } elseif (array_key_exists('body', $this->options)) {
            $this->body = $this->handleBodyRequestData();
            $this->bodyType = 'body';
        } elseif (array_key_exists('form', $this->options)) {
            $this->body = $this->handleFormRequestData();
            $this->bodyType = 'form';
        } elseif (array_key_exists('binary', $this->options)) {
            $this->body = $this->handleBinaryRequestData();
            $this->bodyType = 'binary';
        } else {
            $this->body = '';
            $this->bodyType = 'none';
        }

        return $this->body;
    }

    protected function filterRequestData(array $data): array
    {
        if (!($this->options['filter_nulls'] ?? false)) {
            return $data;
        }

        return array_filter($data, static fn ($item) => null !== $item);
    }

    protected function getHeadersForRequest(): array
    {
        $headers = [];

        foreach ($this->getHeaders() as $key => $value) {
            $headers[] = "{$key}: {$value}";
        }

        return $headers;
    }

    protected function setHeaders(array &$options): self
    {
        $this->headers = $options['headers'] ?? [];

        unset($options['headers']);

        return $this;
    }

    protected function setUri(string $uri): self
    {
        $this->uri = $uri;

        return $this;
    }

    protected function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    protected function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    protected function callAfterCallback(
        string $url,
        RequestException|Response $response,
    ): void {
        if (!isset($this->options['after_callback'])) {
            return;
        }

        foreach ((array) $this->options['after_callback'] as $afterCallback) {
            if (!is_callable($afterCallback)) {
                continue;
            }

            $afterCallback(
                $this->method,
                $url,
                $this->headers,
                $this->getBody(),
                $this->bodyType,
                $response,
            );
        }
    }
}
