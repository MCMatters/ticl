<?php

declare(strict_types = 1);

namespace McMatters\Ticl\Http\Traits;

use InvalidArgumentException;
use function basename, gettype, http_build_query, implode, is_array,
    is_callable, is_resource, is_string, stream_get_contents;

/**
 * Trait RequestDataHandlingTrait
 *
 * @package McMatters\Ticl\Http\Traits
 */
trait RequestDataHandlingTrait
{
    /**
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function handleJsonRequestData(): string
    {
        if (!$this->hasHeader('content-type')) {
            $this->setHeader('content-type', 'application/json');
        }

        if (is_array($this->options['json'])) {
            return json_encode($this->options['json']);
        }

        if (!is_string($this->options['json'])) {
            throw new InvalidArgumentException(
                '"json" key must be as an array or string'
            );
        }

        return $this->options['json'];
    }

    /**
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function handleBodyRequestData(): string
    {
        if (is_array($this->options['body'])) {
            return http_build_query($this->options['body']);
        }

        if (!is_string($this->options['body'])) {
            throw new InvalidArgumentException(
                '"body" key must be an array or string'
            );
        }

        return $this->options['body'];
    }

    /**
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function handleBinaryRequestData()
    {
        if (!is_resource($this->options['binary']) ||
            !is_string($this->options['binary'])
        ) {
            throw new InvalidArgumentException(
                'Binary must be a resource or string'
            );
        }

        return $this->options['binary'];
    }

    /**
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function handleFormRequestData(): string
    {
        $boundary = $this->options['boundary'] ?? sha1(uniqid('', true));
        $boundary = "-------------{$boundary}";
        $eol = "\r\n";

        $this->setHeader('content-type', "multipart/form-data; boundary={$boundary}");

        $content = [];

        foreach ($this->options['form'] as $item) {
            if (!isset($item['name'], $item['contents'])) {
                throw new InvalidArgumentException(
                    '"form" expects "name" and "contents" values'
                );
            }

            $field = "--{$boundary}{$eol}";
            $field .= "Content-Disposition: form-data; name=\"{$item['name']}\"";

            if (!empty($item['filename'])) {
                $field .= '; filename="'.basename($item['filename']).'"';
            }

            $field .= $eol;

            foreach ($item['headers'] ?? [] as $name => $value) {
                if (is_array($value)) {
                    $value = implode(';', $value);
                }

                $field .= "{$name}: {$value}{$eol}";
            }

            $field .= $eol;

            switch (gettype($item['contents'])) {
                case 'resource':
                    $field .= stream_get_contents($item['contents']);
                    break;
                case 'string':
                    $field .= $item['contents'];
                    break;
                case 'object':
                    if (is_callable([$item['content'], '__toString'])) {
                        $field .= ((string) $item['content']);
                    }

                    break;
            }

            $content[] = $field;
        }

        return implode($eol, $content)."--{$boundary}--{$eol}";
    }
}
