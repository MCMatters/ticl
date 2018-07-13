<?php

declare(strict_types = 1);

namespace McMatters\Ticl\Traits;

use const false, true;
use function strtolower;

/**
 * Trait HeadersTrait
 *
 * @package McMatters\Ticl\Traits
 */
trait HeadersTrait
{
    /**
     * @param string $name
     *
     * @return bool
     */
    protected function hasHeader(string $name): bool
    {
        $name = strtolower($name);

        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $names
     *
     * @return bool
     */
    protected function hasHeaders(array $names): bool
    {
        foreach ($names as $name) {
            if (!$this->hasHeader($name)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return self
     */
    protected function setHeader(string $key, $value): self
    {
        $this->headers[$key] = $value;

        return $this;
    }
}
