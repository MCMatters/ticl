<?php

declare(strict_types = 1);

namespace McMatters\Ticl\Http;

use InvalidArgumentException;
use McMatters\Ticl\Exceptions\RequestException;
use McMatters\Ticl\Traits\HeadersTrait;
use const true;
use const CURLINFO_HTTP_CODE, CURLOPT_CUSTOMREQUEST, CURLOPT_FAILONERROR,
    CURLOPT_HEADER, CURLOPT_HTTPHEADER, CURLOPT_NOBODY, CURLOPT_POSTFIELDS,
    CURLOPT_RETURNTRANSFER, CURLOPT_URL;
use function array_key_exists, array_map, curl_close, curl_exec, curl_getinfo,
    curl_init, curl_setopt, http_build_query, is_array, is_bool, is_string,
    json_encode, method_exists, ucfirst;

/**
 * Class Request
 *
 * @package McMatters\Ticl\Http
 */
class Request
{
    use HeadersTrait;

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
        curl_setopt($this->curl, CURLOPT_URL, $this->getUriForRequest());
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->getHeadersForRequest());
        curl_setopt($this->curl, CURLOPT_HEADER, true);

        $this->setOptionsDependOnMethod();

        $response = curl_exec($this->curl);

        if (curl_getinfo($this->curl, CURLINFO_HTTP_CODE) >= 400) {
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
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->getPostDataFromOptions());
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
            if (is_array($this->options['query'])) {
                $query = $this->options['query'];

                if ($this->options['bool_as_string'] ?? false) {
                    $query = array_map(
                        [$this, 'castBoolToString'],
                        $this->options['query']
                    );
                }

                return $this->uri .= '?'.http_build_query($query);
            }

            if (is_string($this->options['query'])) {
                return $this->uri .= '?'.ltrim($this->options['query'], '?');
            }

            throw new InvalidArgumentException('"query" must be as an array or string');
        }

        return $this->uri;
    }

    /**
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function getPostDataFromOptions(): string
    {
        if (array_key_exists('json', $this->options)) {
            if (!$this->hasHeader('content-type')) {
                $this->setHeader('content-type', 'application/json');
            }

            if (is_array($this->options['json'])) {
                return json_encode($this->options['json']);
            }

            if (!is_string($this->options['json'])) {
                throw new InvalidArgumentException(
                    '"json" key must be as an array or string'
                );
            }

            return $this->options['json'];
        }

        if (array_key_exists('body', $this->options)) {
            if (is_array($this->options['body'])) {
                return http_build_query($this->options['body']);
            }

            if (!is_string($this->options['body'])) {
                throw new InvalidArgumentException(
                    '"body" key must be as an array or string'
                );
            }

            return $this->options['body'];
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

    /**
     * @param mixed $item
     *
     * @return mixed
     */
    protected function castBoolToString($item)
    {
        if (is_bool($item)) {
            return $item ? 'true' : 'false';
        }

        return $item;
    }
}
