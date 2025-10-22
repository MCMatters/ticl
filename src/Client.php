<?php

declare(strict_types=1);

namespace McMatters\Ticl;

use InvalidArgumentException;
use McMatters\Ticl\Exceptions\RequestException;
use McMatters\Ticl\Http\Request;
use McMatters\Ticl\Http\Response;

use function array_replace_recursive;
use function ltrim;
use function max;
use function parse_url;
use function rtrim;

use const false;
use const null;
use const PHP_URL_HOST;
use const true;

class Client
{
    protected array $config;

    protected ?Request $request;

    protected array $with = [];

    protected static array $globalConfig = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \McMatters\Ticl\Exceptions\RequestException
     */
    public function head(string $uri, array $options = []): Response
    {
        return $this->call('head', $uri, $options);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \McMatters\Ticl\Exceptions\RequestException
     */
    public function get(string $uri, array $options = []): Response
    {
        return $this->call('get', $uri, $options);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \McMatters\Ticl\Exceptions\RequestException
     */
    public function post(string $uri, array $options = []): Response
    {
        return $this->call('post', $uri, $options);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \McMatters\Ticl\Exceptions\RequestException
     */
    public function put(string $uri, array $options = []): Response
    {
        return $this->call('put', $uri, $options);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \McMatters\Ticl\Exceptions\RequestException
     */
    public function patch(string $uri, array $options = []): Response
    {
        return $this->call('patch', $uri, $options);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \McMatters\Ticl\Exceptions\RequestException
     */
    public function delete(string $uri, array $options = []): Response
    {
        return $this->call('delete', $uri, $options);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function getFullUrl(string $uri, array $options = []): string
    {
        if (!empty($uri) && ($options['skip_base_uri'] ?? false)) {
            return $uri;
        }

        if (!empty($options['base_uri'])) {
            $uri = ltrim($uri, '/');

            if ('' === $uri) {
                return rtrim($options['base_uri'], '/');
            }

            return rtrim($options['base_uri'], '/')."/{$uri}";
        }

        if (null === parse_url($uri, PHP_URL_HOST)) {
            throw new InvalidArgumentException('"uri" must be a valid url');
        }

        return $uri;
    }

    public function withQuery(array $query, bool $replace = false): self
    {
        $this->with['query'] = $replace
            ? $query
            : array_replace_recursive($this->with['query'] ?? [], $query);

        return $this;
    }

    public function withJson(array $data, bool $replace = false): self
    {
        $this->with['json'] = $replace
            ? $data
            : array_replace_recursive($this->with['json'] ?? [], $data);

        return $this;
    }

    public function withHeaders(array $data, bool $replace = false): self
    {
        $this->with['headers'] = $replace
            ? $data
            : array_replace_recursive($this->with['headers'] ?? [], $data);

        return $this;
    }

    public function setDefaultHeaders(array $data, bool $replace = false): self
    {
        $this->config['headers'] = $replace
            ? $data
            : array_replace_recursive($this->config['headers'] ?? [], $data);

        return $this;
    }

    public static function setGlobalConfig(string $key, mixed $value): void
    {
        self::$globalConfig[$key] = $value;
    }

    public static function clearGlobalConfig(): void
    {
        self::$globalConfig = [];
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \McMatters\Ticl\Exceptions\RequestException
     */
    protected function call(
        string $method,
        string $uri,
        array $options = [],
    ): Response {
        $options = $this->prepareOptions($options);
        $uri = $this->getFullUrl($uri, $options);

        if (!($options['keep_alive'] ?? false)) {
            $request = new Request($method, $uri, $options);
        } else {
            if (!isset($this->request)) {
                $this->request = $request = new Request($method, $uri, $options);
            } else {
                $request = $this->request->setDefaults($method, $uri, $options);
            }

            unset($options['keep_alive']);
        }

        $this->with = [];

        return $this->sendRequest($request, $options);
    }

    protected function sendRequest(
        Request $request,
        array $options = [],
    ): Response {
        $attempts = 0;
        $retryCount = max($options['retry_count'] ?? 1, 1);
        $retrySleep = $options['retry_sleep'] ?? 0;

        do {
            try {
                return $request->send();
            } catch (RequestException $e) {
                if (++$attempts >= $retryCount) {
                    throw $e;
                }

                usleep($retrySleep * 1000);
            }
        } while (true);
    }

    protected function prepareOptions(array $options = []): array
    {
        return array_replace_recursive(
            self::$globalConfig,
            $this->config,
            $this->with,
            $options,
        );
    }
}
