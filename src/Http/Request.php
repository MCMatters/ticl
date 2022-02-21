<?php

declare(strict_types=1);

namespace McMatters\Ticl\Http;

use McMatters\Ticl\Enums\HttpStatusCode;
use McMatters\Ticl\Exceptions\RequestException;
use McMatters\Ticl\Http\Traits\RequestDataHandlingTrait;
use McMatters\Ticl\Http\Traits\RequestQueryHandlingTrait;
use McMatters\Ticl\Traits\HeadersTrait;

use function array_key_exists;
use function curl_close;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_reset;
use function curl_setopt;
use function is_bool;
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

/**
 * Class Request
 *
 * @package McMatters\Ticl\Http
 */
class Request
{
    use HeadersTrait;
    use RequestDataHandlingTrait;
    use RequestQueryHandlingTrait;

    /**
     * @var resource
     */
    protected $curl;

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * Request constructor.
     *
     * @param string $method
     * @param string $uri
     * @param array $options
     */
    public function __construct(
        string $method,
        string $uri,
        array $options = []
    ) {
        $this->curl = curl_init();

        $this->setDefaults($method, $uri, $options);
    }

    /**
     * Request destructor.
     */
    public function __destruct()
    {
        if ($this->curl) {
            curl_close($this->curl);
        }
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     *
     * @return \McMatters\Ticl\Http\Request
     */
    public function setDefaults(
        string $method,
        string $uri,
        array $options = []
    ): self {
        $this->setHeaders($options)
            ->setUri($uri)
            ->setMethod($method)
            ->setOptions($options);

        return $this;
    }

    /**
     * @return \McMatters\Ticl\Http\Response
     *
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

        $response = curl_exec($this->curl);

        if (curl_getinfo($this->curl, CURLINFO_HTTP_CODE) >= HttpStatusCode::BAD_REQUEST) {
            throw new RequestException(is_bool($response) ? '' : $response, $this->curl);
        }

        if (false === $response) {
            throw new RequestException('', $this->curl);
        }

        return new Response($this->curl, is_bool($response) ? '' : $response);
    }

    /**
     * @return void
     */
    protected function setOptionsDependOnMethod()
    {
        $method = 'prepare'.ucfirst($this->method).'Request';

        if (method_exists($this, $method)) {
            $this->{$method}();
        }
    }

    /**
     * @return void
     */
    protected function setCurlOptions()
    {
        foreach ($this->options['curl'] ?? [] as $key => $value) {
            curl_setopt($this->curl, $key, $value);
        }
    }

    /**
     * @return void
     */
    protected function prepareHeadRequest()
    {
        curl_setopt($this->curl, CURLOPT_NOBODY, true);
        $this->prepareGetRequest();
    }

    /**
     * @return void
     */
    protected function prepareGetRequest()
    {
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_FAILONERROR, false);
    }

    /**
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function preparePostRequest()
    {
        $this->prepareGetRequest();
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->getRequestData());
    }

    /**
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function preparePutRequest()
    {
        $this->preparePostRequest();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
    }

    /**
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function preparePatchRequest()
    {
        $this->preparePostRequest();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
    }

    /**
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function prepareDeleteRequest()
    {
        $this->preparePostRequest();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    /**
     * @return void
     */
    protected function setUpRedirects()
    {
        if ($this->options['follow_redirects'] ?? true) {
            curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($this->curl, CURLOPT_MAXREDIRS, $this->options['max_redirects'] ?? 5);
        } else {
            curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, false);
        }
    }

    /**
     * @return string
     *
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
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function getRequestData(): string
    {
        if (array_key_exists('json', $this->options)) {
            return $this->handleJsonRequestData();
        }

        if (array_key_exists('body', $this->options)) {
            return $this->handleBodyRequestData();
        }

        if (array_key_exists('form', $this->options)) {
            return $this->handleFormRequestData();
        }

        if (array_key_exists('binary', $this->options)) {
            return $this->handleBinaryRequestData();
        }

        return '';
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function filterRequestData(array $data): array
    {
        if (!($this->options['filter_nulls'] ?? false)) {
            return $data;
        }

        $filtered = [];

        foreach ($data as $key => $item) {
            if (null !== $item) {
                $filtered[$key] = $item;
            }
        }

        return $filtered;
    }

    /**
     * @return array
     */
    protected function getHeadersForRequest(): array
    {
        $headers = [];

        foreach ($this->headers as $key => $value) {
            $headers[] = "{$key}: {$value}";
        }

        return $headers;
    }


    /**
     * @param array $options
     *
     * @return \McMatters\Ticl\Http\Request
     */
    protected function setHeaders(array &$options): self
    {
        $this->headers = $options['headers'] ?? [];

        unset($options['headers']);

        return $this;
    }

    /**
     * @param string $uri
     *
     * @return \McMatters\Ticl\Http\Request
     */
    protected function setUri(string $uri): self
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * @param string $method
     *
     * @return \McMatters\Ticl\Http\Request
     */
    protected function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @param array $options
     *
     * @return \McMatters\Ticl\Http\Request
     */
    protected function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }
}
