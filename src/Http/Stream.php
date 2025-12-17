<?php

namespace Villaflor\Connection\Http;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

class Stream implements StreamInterface
{
    private string $content;

    private int $position = 0;

    public function __construct(string $content = '')
    {
        $this->content = $content;
    }

    public function __toString(): string
    {
        return $this->content;
    }

    public function close(): void
    {
        // No-op for string-based stream
    }

    public function detach()
    {
        $this->content = '';

        return null;
    }

    public function getSize(): ?int
    {
        return strlen($this->content);
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function eof(): bool
    {
        return $this->position >= strlen($this->content);
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $length = strlen($this->content);

        switch ($whence) {
            case SEEK_SET:
                $this->position = $offset;
                break;
            case SEEK_CUR:
                $this->position += $offset;
                break;
            case SEEK_END:
                $this->position = $length + $offset;
                break;
            default:
                throw new RuntimeException('Invalid whence parameter');
        }

        if ($this->position < 0) {
            $this->position = 0;
        } elseif ($this->position > $length) {
            $this->position = $length;
        }
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function isWritable(): bool
    {
        return true;
    }

    public function write(string $string): int
    {
        $this->content .= $string;
        $this->position = strlen($this->content);

        return strlen($string);
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read(int $length): string
    {
        $result = substr($this->content, $this->position, $length);
        $this->position += strlen($result);

        return $result;
    }

    public function getContents(): string
    {
        $result = substr($this->content, $this->position);
        $this->position = strlen($this->content);

        return $result;
    }

    public function getMetadata(?string $key = null)
    {
        return $key === null ? [] : null;
    }
}
