<?php

declare(strict_types = 1);

namespace McMatters\Ticl\Http\Traits;

use InvalidArgumentException;
use const PHP_QUERY_RFC1738;
use const false;
use function array_map, http_build_query, ini_get, is_array, is_bool, is_string, ltrim;

/**
 * Trait RequestQueryHandlingTrait
 *
 * @package McMatters\Ticl\Http\Traits
 */
trait RequestQueryHandlingTrait
{
    /**
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function handleQueryRequest(): string
    {
        if (is_array($this->options['query'])) {
            if (empty($this->options['query'])) {
                return $this->uri;
            }

            $query = $this->options['query'];

            if ($this->options['query_params']['bool_as_string'] ?? false) {
                $query = array_map(
                    function ($item) {
                        if (is_bool($item)) {
                            return $item ? 'true' : 'false';
                        }

                        return $item;
                    },
                    $this->options['query']
                );
            }

            $query = http_build_query(
                $query,
                $this->options['query_params']['numeric_prefix'] ?? '',
                $this->options['query_params']['arg_separator'] ?? ini_get('arg_separator.output'),
                $this->options['query_params']['enc_type'] ?? PHP_QUERY_RFC1738
            );

            return "{$this->uri}?{$query}";
        }

        if (is_string($this->options['query'])) {
            return $this->uri.'?'.ltrim($this->options['query'], '?');
        }

        throw new InvalidArgumentException('"query" must be as an array or string');
    }
}
