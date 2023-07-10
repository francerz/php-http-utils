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

    public function __toString()
    {
        return $this->content;
    }

    public function close()
    {
    }

    public function detach()
    {
        return null;
    }

    public function getSize()
    {
        return strlen($this->content);
    }

    public function tell()
    {
        return $this->pointer;
    }

    public function eof()
    {
        return $this->tell() >= $this->getSize();
    }

    public function isSeekable()
    {
        return true;
    }

    public function seek($offset, $whence = SEEK_SET)
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

    public function rewind()
    {
        $this->seek(0);
    }

    public function isWritable()
    {
        return false;
    }

    public function write($string)
    {
    }

    public function isReadable()
    {
        return true;
    }

    public function read($length)
    {
        $bytes = substr($this->content, $this->pointer, $length);
        $this->pointer += $length;
        $this->pointer = min($this->pointer, $this->getSize());
        return $bytes;
    }

    public function getContents()
    {
        return $this->content;
    }

    public function getMetadata($key = null)
    {
        return null;
    }
}
