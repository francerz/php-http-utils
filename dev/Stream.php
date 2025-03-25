<?php

namespace Francerz\HttpUtils\Dev;

use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{
    private $pointer = 0;
    private $content;

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
    }

    public function detach()
    {
        return null;
    }

    public function getSize(): ?int
    {
        return strlen($this->content);
    }

    public function tell(): int
    {
        return $this->pointer;
    }

    public function eof(): bool
    {
        return $this->tell() >= $this->getSize();
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        switch ($whence) {
            case SEEK_SET:
                $this->pointer = $offset;
                break;
            case SEEK_END:
                $this->pointer = $this->getSize() + $offset;
                break;
            case SEEK_CUR:
                $this->pointer += $offset;
                break;
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write($string): int
    {
        return 0;
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read($length): string
    {
        $bytes = substr($this->content, $this->pointer, $length);
        $this->pointer += $length;
        $this->pointer = min($this->pointer, $this->getSize());
        return $bytes;
    }

    public function getContents(): string
    {
        return $this->content;
    }

    public function getMetadata($key = null)
    {
        return null;
    }
}
