<?php

declare(strict_types = 1);

namespace McMatters\Ticl;

use BadMethodCallException;
use InvalidArgumentException;
use McMatters\Ticl\Http\Request;
use McMatters\Ticl\Http\Response;
use const null, true;
use const PHP_URL_HOST;
use function array_merge_recursive, in_array, is_string, ltrim, parse_url, rtrim, strtolower;

/**
 * Class Client
 *
 * @package McMatters\Ticl
 * @method Response head(string $uri, array $options = [])
 * @method Response get(string $uri, array $options = [])
 * @method Response post(string $uri, array $options = [])
 * @method Response put(string $uri, array $options = [])
 * @method Response patch(string $uri, array $options = [])
 * @method Response delete(string $uri, array $options = [])
 */
class Client
{
    /**
     * @var array
     */
    protected $headers;

    /**
     * @var string
     */
    protected $baseUri;

    /**
     * Client constructor.
     *
     * @param string $baseUri
     * @param array $headers
     */
    public function __construct(string $baseUri = '', array $headers = [])
    {
        $this->baseUri = $baseUri;
        $this->headers = $headers;
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return \McMatters\Ticl\Http\Response
     * @throws \BadMethodCallException
     * @throws \InvalidArgumentException
     * @throws \McMatters\Ticl\Exceptions\RequestException
     */
    public function __call(string $name, array $arguments = [])
    {
        $name = strtolower($name);

        $methods = ['head', 'get', 'post', 'put', 'patch', 'delete'];

        if (!in_array($name, $methods, true)) {
            throw new BadMethodCallException();
        }

        return (new Request(
            $name,
            $this->prepareUri($arguments),
            $this->prepareOptions($arguments)
        ))->send();
    }

    /**
     * @param array $arguments
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function prepareUri(array $arguments = []): string
    {
        if (!isset($arguments[0]) || !is_string($arguments[0])) {
            throw new InvalidArgumentException('"url" must be as a string');
        }

        $uri = $arguments[0];

        if ($this->baseUri) {
            return rtrim($this->baseUri, '/').'/'.ltrim($uri, '/');
        }

        if (null === parse_url($uri, PHP_URL_HOST)) {
            throw new InvalidArgumentException('"uri" must be a valid url');
        }

        return $uri;
    }

    /**
     * @param array $arguments
     *
     * @return array
     */
    protected function prepareOptions(array $arguments = []): array
    {
        $options = $arguments[1] ?? [];

        $options['headers'] = array_merge_recursive(
            $options['headers'] ?? [],
            $this->headers
        );

        return $options;
    }
}
