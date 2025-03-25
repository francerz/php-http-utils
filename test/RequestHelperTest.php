<?php

namespace Francerz\Http\Utils\Tests;

use Francerz\Http\Utils\RequestHelper;
use Francerz\HttpUtils\Dev\Request;
use PHPUnit\Framework\TestCase;

class RequestHelperTest extends TestCase
{
    public function testStringify()
    {
        $request = new Request('http://www.example.com/test/path?query=string#fragment');
        $request = $request->withHeader('Host', 'www.example.com');

        $this->assertEquals(
            "GET /test/path?query=string HTTP/1.1\n" .
            "Host: www.example.com\n" .
            "\n",
            RequestHelper::stringify($request)
        );
    }
}
