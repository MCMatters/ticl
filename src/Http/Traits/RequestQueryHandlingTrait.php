<?php

declare(strict_types=1);

namespace McMatters\Ticl\Http\Traits;

use InvalidArgumentException;

use function array_map;
use function http_build_query;
use function ini_get;
use function is_array;
use function is_bool;
use function is_string;
use function ltrim;
use function parse_str;
use function parse_url;

use const false;
use const PHP_QUERY_RFC1738;

trait RequestQueryHandlingTrait
{
    /**
     * @throws \InvalidArgumentException
     */
    protected function handleQueryRequest(): string
    {
        if (empty($this->options['query'])) {
            return $this->uri;
        }

        if (is_array($this->options['query'])) {
            $baseQueryString = parse_url($this->uri, PHP_URL_QUERY);
            $baseQuery = [];

            if (null !== $baseQueryString) {
                parse_str($baseQueryString, $baseQuery);
                $this->uri = mb_substr($this->uri, 0, mb_strpos($this->uri, '?'));
            }

            if ($this->options['skip_base_uri'] ?? false) {
                $this->options['query'] = $baseQuery + $this->options['query'];
            } else {
                $this->options['query'] += $baseQuery;
            }

            $query = $this->options['query'];

            if ($this->options['query_params']['bool_as_string'] ?? false) {
                $query = array_map(
                    static function ($item) {
                        if (is_bool($item)) {
                            return $item ? 'true' : 'false';
                        }

                        return $item;
                    },
                    $this->options['query'],
                );
            }

            $query = http_build_query(
                $query,
                $this->options['query_params']['numeric_prefix'] ?? '',
                $this->options['query_params']['arg_separator'] ?? ini_get('arg_separator.output'),
                $this->options['query_params']['enc_type'] ?? PHP_QUERY_RFC1738,
            );

            return "{$this->uri}?{$query}";
        }

        if (is_string($this->options['query'])) {
            return $this->uri.'?'.ltrim($this->options['query'], '?');
        }

        throw new InvalidArgumentException(
            '"query" must be as an array or string',
        );
    }
}
