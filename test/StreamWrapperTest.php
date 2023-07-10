<?php

namespace Francerz\Http\Utils;

use Francerz\HttpUtils\Dev\Stream;
use PHPUnit\Framework\TestCase;

class StreamWrapperTest extends TestCase
{
    public function testReadLine()
    {
        $stream = new Stream('This is the content.');
        $wrapper = new StreamWrapper($stream);

        $this->assertEquals('This is the content.', $wrapper->readLine());
        $this->assertEquals('', $wrapper->readLine());

        $stream = new Stream(
            "Reading the contents.\n" .
            "From the file\n" .
            "Until end."
        );
        $wrapper = new StreamWrapper($stream);

        $this->assertEquals('Reading the contents.', $wrapper->readLine());
        $this->assertEquals('From the file', $wrapper->readLine());
        $this->assertEquals('Until end.', $wrapper->readLine());
        $this->assertEquals('', $wrapper->readLine());

    }
}
