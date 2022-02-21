<?php

declare(strict_types=1);

namespace McMatters\Ticl;

use InvalidArgumentException;
use McMatters\Ticl\Http\Request;
use McMatters\Ticl\Http\Response;

use function array_replace_recursive;
use function ltrim;
use function parse_url;
use function rtrim;

use const false;
use const null;
use const PHP_URL_HOST;

/**
 * Class Client
 *
 * @package McMatters\Ticl
 */
class Client
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var \McMatters\Ticl\Http\Request|null
     */
    protected $request;

    /**
     * @var array
     */
    protected $with = [];

    /**
     * Client constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * @param string $uri
     * @param array $options
     *
     * @return \McMatters\Ticl\Http\Response
     *
     * @throws \InvalidArgumentException
     * @throws \McMatters\Ticl\Exceptions\RequestException
     */
    public function head(string $uri, array $options = []): Response
    {
        return $this->call('head', $uri, $options);
    }

    /**
     * @param string $uri
     * @param array $options
     *
     * @return \McMatters\Ticl\Http\Response
     *
     * @throws \InvalidArgumentException
     * @throws \McMatters\Ticl\Exceptions\RequestException
     */
    public function get(string $uri, array $options = []): Response
    {
        return $this->call('get', $uri, $options);
    }

    /**
     * @param string $uri
     * @param array $options
     *
     * @return \McMatters\Ticl\Http\Response
     *
     * @throws \InvalidArgumentException
     * @throws \McMatters\Ticl\Exceptions\RequestException
     */
    public function post(string $uri, array $options = []): Response
    {
        return $this->call('post', $uri, $options);
    }

    /**
     * @param string $uri
     * @param array $options
     *
     * @return \McMatters\Ticl\Http\Response
     *
     * @throws \InvalidArgumentException
     * @throws \McMatters\Ticl\Exceptions\RequestException
     */
    public function put(string $uri, array $options = []): Response
    {
        return $this->call('put', $uri, $options);
    }

    /**
     * @param string $uri
     * @param array $options
     *
     * @return \McMatters\Ticl\Http\Response
     *
     * @throws \InvalidArgumentException
     * @throws \McMatters\Ticl\Exceptions\RequestException
     */
    public function patch(string $uri, array $options = []): Response
    {
        return $this->call('patch', $uri, $options);
    }

    /**
     * @param string $uri
     * @param array $options
     *
     * @return \McMatters\Ticl\Http\Response
     *
     * @throws \InvalidArgumentException
     * @throws \McMatters\Ticl\Exceptions\RequestException
     */
    public function delete(string $uri, array $options = []): Response
    {
        return $this->call('delete', $uri, $options);
    }

    /**
     * @param string $uri
     * @param array $options
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function getFullUrl(string $uri, array $options = []): string
    {
        if (!empty($uri) && ($options['skip_base_uri'] ?? false)) {
            return $uri;
        }

        if (!empty($this->config['base_uri'])) {
            $uri = ltrim($uri, '/');

            if ('' === $uri) {
                return rtrim($this->config['base_uri'], '/');
            }

            return rtrim($this->config['base_uri'], '/')."/{$uri}";
        }

        if (null === parse_url($uri, PHP_URL_HOST)) {
            throw new InvalidArgumentException('"uri" must be a valid url');
        }

        return $uri;
    }

    /**
     * @param array $query
     * @param bool $replace
     *
     * @return self
     */
    public function withQuery(array $query, bool $replace = false): self
    {
        $this->with['query'] = $replace
            ? $query
            : array_replace_recursive($this->with['query'] ?? [], $query);

        return $this;
    }

    /**
     * @param array $data
     * @param bool $replace
     *
     * @return self
     */
    public function withJson(array $data, bool $replace = false): self
    {
        $this->with['json'] = $replace
            ? $data
            : array_replace_recursive($this->with['json'] ?? [], $data);

        return $this;
    }

    /**
     * @param array $data
     * @param bool $replace
     *
     * @return self
     */
    public function withHeaders(array $data, bool $replace = false): self
    {
        $this->with['headers'] = $replace
            ? $data
            : array_replace_recursive($this->with['headers'] ?? [], $data);

        return $this;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     *
     * @return \McMatters\Ticl\Http\Response
     *
     * @throws \InvalidArgumentException
     * @throws \McMatters\Ticl\Exceptions\RequestException
     */
    protected function call(
        string $method,
        string $uri,
        array $options = []
    ): Response {
        $uri = $this->getFullUrl($uri, $options);
        $options = $this->prepareOptions($options);

        if (!($options['keep_alive'] ?? false)) {
            $request = new Request($method, $uri, $options);
        } else {
            if (null === $this->request) {
                $this->request = $request = new Request($method, $uri, $options);
            } else {
                $request = $this->request->setDefaults($method, $uri, $options);
            }

            unset($options['keep_alive']);
        }

        $this->with = [];

        return $request->send();
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function prepareOptions(array $options = []): array
    {
        return array_replace_recursive($this->config, $this->with, $options);
    }
}
