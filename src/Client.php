<?php

declare(strict_types = 1);

namespace McMatters\Ticl;

use InvalidArgumentException;
use McMatters\Ticl\Http\Request;
use McMatters\Ticl\Http\Response;
use const null;
use const PHP_URL_HOST;
use function array_replace_recursive, ltrim, parse_url, rtrim;

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
        $request = new Request(
            $method,
            $this->prepareUri($uri),
            $this->prepareOptions($options)
        );

        return $request->send();
    }

    /**
     * @param string $uri
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function prepareUri(string $uri): string
    {
        if (!empty($this->config['base_uri'])) {
            return rtrim($this->config['base_uri'], '/').'/'.ltrim($uri, '/');
        }

        if (null === parse_url($uri, PHP_URL_HOST)) {
            throw new InvalidArgumentException('"uri" must be a valid url');
        }

        return $uri;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function prepareOptions(array $options = []): array
    {
        return array_replace_recursive($options, $this->config);
    }
}
