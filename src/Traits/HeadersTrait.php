<?php

declare(strict_types=1);

namespace McMatters\Ticl\Traits;

use function is_callable;
use function mb_strtolower;

use const false;
use const true;

trait HeadersTrait
{
    public function hasHeader(string $name): bool
    {
        $name = mb_strtolower($name);

        foreach ($this->headers as $key => $value) {
            if (mb_strtolower($key) === $name) {
                return true;
            }
        }

        return false;
    }

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
     * @param mixed $value
     */
    protected function setHeader(string $key, $value): self
    {
        $this->headers[$key] = $value;

        return $this;
    }
}
