<?php

declare(strict_types = 1);

namespace McMatters\Ticl\Http;

use McMatters\Ticl\Enums\HttpStatusCode;
use McMatters\Ticl\Exceptions\RequestException;
use McMatters\Ticl\Http\Traits\RequestDataHandlingTrait;
use McMatters\Ticl\Http\Traits\RequestQueryHandlingTrait;
use McMatters\Ticl\Traits\HeadersTrait;
use const true;
use const CURLINFO_HTTP_CODE, CURLOPT_CUSTOMREQUEST, CURLOPT_FAILONERROR,
    CURLOPT_HEADER, CURLOPT_HTTPHEADER, CURLOPT_NOBODY, CURLOPT_POSTFIELDS,
    CURLOPT_RETURNTRANSFER, CURLOPT_URL;
use function array_key_exists, curl_close, curl_exec, curl_getinfo, curl_init,
    curl_setopt, method_exists, ucfirst;

/**
 * Class Request
 *
 * @package McMatters\Ticl\Http
 */
class Request
{
    use HeadersTrait, RequestDataHandlingTrait, RequestQueryHandlingTrait;

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
    public function __construct(string $method, string $uri, array $options = [])
    {
        $this->curl = curl_init();
        $this->setHeaders($options);

        $this->uri = $uri;
        $this->method = $method;
        $this->options = $options;
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
     * @return \McMatters\Ticl\Http\Response
     * @throws \InvalidArgumentException
     * @throws \McMatters\Ticl\Exceptions\RequestException
     */
    public function send(): Response
    {
        $this->setOptionsDependOnMethod();

        curl_setopt($this->curl, CURLOPT_URL, $this->getUriForRequest());
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->getHeadersForRequest());
        curl_setopt($this->curl, CURLOPT_HEADER, true);

        $response = curl_exec($this->curl);

        if (curl_getinfo($this->curl, CURLINFO_HTTP_CODE) >= HttpStatusCode::BAD_REQUEST) {
            throw new RequestException($response, $this->curl);
        }

        return new Response($this->curl, $response);
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
    protected function prepareHeadRequest()
    {
        curl_setopt($this->curl, CURLOPT_NOBODY, true);
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
     * @throws \InvalidArgumentException
     */
    protected function preparePostRequest()
    {
        $this->prepareGetRequest();
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->getRequestData());
    }

    /**
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function preparePutRequest()
    {
        $this->preparePostRequest();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
    }

    /**
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function preparePatchRequest()
    {
        $this->preparePostRequest();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
    }

    /**
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function prepareDeleteRequest()
    {
        $this->preparePostRequest();
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    /**
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function getUriForRequest(): string
    {
        if (array_key_exists('query', $this->options)) {
            $this->handleQueryRequest();
        }

        return $this->uri;
    }

    /**
     * @return string
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

        return '';
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
     * @return void
     */
    protected function setHeaders(array &$options)
    {
        $this->headers = $options['headers'] ?? [];

        unset($options['headers']);
    }
}
