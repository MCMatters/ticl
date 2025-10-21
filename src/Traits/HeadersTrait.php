<?php

declare(strict_types=1);

namespace McMatters\Ticl\Traits;

use stdClass;

use function is_callable;
use function mb_strtolower;

use const false;
use const true;

trait HeadersTrait
{
    public function hasHeader(string $name): bool
    {
        $default = new stdClass();

        return $default !== $this->getHeader($name, $default);
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

    public function getHeader(string $name, mixed $default = null): mixed
    {
        $name = mb_strtolower($name);

        foreach ($this->headers as $key => $value) {
            if (mb_strtolower($key) === $name) {
                return $value;
            }
        }

        return is_callable($default) ? $default() : $default;
    }

    protected function setHeader(string $key, mixed $value): self
    {
        $this->headers[$key] = $value;

        return $this;
    }
}
