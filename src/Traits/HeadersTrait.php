<?php

declare(strict_types=1);

namespace McMatters\Ticl\Traits;

use function is_callable, strtolower;

use const false, true;

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
    public function hasHeader(string $name): bool
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
    public function hasHeaders(array $names): bool
    {
        foreach ($names as $name) {
            if (!$this->hasHeader($name)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getHeader(string $name, $default = null)
    {
        $name = strtolower($name);

        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $name) {
                return $value;
            }
        }

        return is_callable($default) ? $default() : $default;
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
