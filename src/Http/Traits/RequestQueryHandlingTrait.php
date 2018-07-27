<?php

declare(strict_types = 1);

namespace McMatters\Ticl\Http\Traits;

use InvalidArgumentException;
use const false;
use function array_map, http_build_query, is_array, is_bool, is_string, ltrim;

/**
 * Trait RequestQueryHandlingTrait
 *
 * @package McMatters\Ticl\Http\Traits
 */
trait RequestQueryHandlingTrait
{
    /**
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function handleQueryRequest(): string
    {
        if (is_array($this->options['query'])) {
            if (empty($this->options['query'])) {
                return $this->uri;
            }

            $query = $this->options['query'];

            if ($this->options['bool_as_string'] ?? false) {
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

            return $this->uri .= '?'.http_build_query($query);
        }

        if (is_string($this->options['query'])) {
            return $this->uri .= '?'.ltrim($this->options['query'], '?');
        }

        throw new InvalidArgumentException('"query" must be as an array or string');
    }
}
