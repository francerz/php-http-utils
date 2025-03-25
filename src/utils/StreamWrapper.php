<?php

namespace Francerz\Http\Utils;

use Psr\Http\Message\StreamInterface;

class StreamWrapper implements StreamInterface
{
    private $stream;
    private $lastLine = '';

    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
    }

    public function __toString(): string
    {
        return $this->stream->__toString();
    }

    public function close(): void
    {
        $this->stream->close();
    }

    public function detach()
    {
        return $this->stream->detach();
    }

    public function getSize(): ?int
    {
        return $this->stream->getSize();
    }

    public function tell(): int
    {
        return $this->stream->tell();
    }

    public function eof(): bool
    {
        return $this->stream->eof();
    }

    public function isSeekable(): bool
    {
        return $this->stream->isSeekable();
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        $this->stream->seek($offset, $whence);
    }

    public function rewind(): void
    {
        $this->stream->rewind();
    }

    public function isWritable(): bool
    {
        return $this->stream->isWritable();
    }

    public function write($string): int
    {
        return $this->stream->write($string);
    }

    public function isReadable(): bool
    {
        return $this->stream->isReadable();
    }

    public function read($length): string
    {
        return $this->stream->read($length);
    }

    public function getContents(): string
    {
        return $this->stream->getContents();
    }

    public function getMetadata($key = null)
    {
        return $this->stream->getMetadata($key);
    }

    /**
     * Reads line from stream.
     *
     * @param string $eol Read up bytes from the object and returns until $eof
     *                    sequence is found or eof reached.
     * @return string
     *
     * @throws \RuntimeException if an error occurs.
     */
    public function readLine($eol = "\n")
    {
        $eolLen = strlen($eol);
        while (!$this->stream->eof()) {
            $pos = strpos($this->lastLine, $eol);
            if ($pos !== false) {
                $line = substr($this->lastLine, 0, $pos + $eolLen);
                $this->lastLine = substr($this->lastLine, $pos + $eolLen);
                return $line;
            }
            $this->lastLine .= $this->stream->read(4096);
        }

        $pos = strpos($this->lastLine, $eol);
        if ($pos !== false) {
            $line = substr($this->lastLine, 0, $pos + $eolLen);
            $this->lastLine = substr($this->lastLine, $pos + $eolLen);
            return $line;
        }

        $line = $this->lastLine;
        $this->lastLine = '';
        return $line;
    }
}
