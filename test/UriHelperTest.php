<?php

namespace Francerz\Http\Utils\Tests;

use Francerz\Http\Utils\UriHelper;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function Francerz\Http\Utils\baseUrl;
use function Francerz\Http\Utils\siteUrl;

class UriHelperTest extends TestCase
{
    private static function callPrivateStatic($obj, string $method, ...$args)
    {
        $ref = new ReflectionClass($obj);
        $method = $ref->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs(null, $args);
    }
    public function testUriMapping()
    {
        $uri = static::callPrivateStatic(
            UriHelper::class,
            'mapReplaceString',
            'https://example.com/collection/{id}/{id2}',
            ['id' => 20, 'id2' => 30]
        );

        $this->assertEquals('https://example.com/collection/20/30', $uri);
    }

    public function testEncodeParams()
    {
        $ref = new ReflectionClass(UriHelper::class);
        $method = $ref->getMethod('mixUrlEncodedParams');
        $method->setAccessible(true);

        $this->assertEquals('a=1&b=2', $method->invokeArgs(null, [
            '',                     // encoded_string
            ['a' => 1, 'b' => 2]    // map
        ]));
        $this->assertEquals('a=1&b%5B0%5D=2&b%5B1%5D=3', $method->invokeArgs(null, [
            '',
            ['a' => 1, 'b' => [2, 3]]
        ]));
    }

    public function testGetPathInfo()
    {
        $this->assertEquals('/some/path', UriHelper::getPathInfo('/some/path', '/'));
        $this->assertEquals('/some/path', UriHelper::getPathInfo('/some/path', '/index.php'));
        $this->assertEquals('/some/path', UriHelper::getPathInfo('/webapp/some/path', '/webapp/index.php'));
        $this->assertEquals('/some/path', UriHelper::getPathInfo('/index.php/some/path', '/index.php'));
        $this->assertEquals('/some/path', UriHelper::getPathInfo('/webapp/index.php/some/path', '/webapp/index.php'));
        $this->assertEquals('/some/path', UriHelper::getPathInfo('/webapp/some/path', '/webapp'));
        $this->assertEquals('/some/path', UriHelper::getPathInfo('/webapp/index.php/some/path?var=data', '/webapp/index.php'));
    }

    public function testGetBaseUrl()
    {
        $this->assertEquals(
            'http://localhost/assets/css/style.css',
            baseUrl('/assets/css/style.css', [
                'HTTP_HOST' => 'localhost',
                'SCRIPT_NAME' => '/index.php'
            ], false)
        );
        $this->assertEquals(
            'https://localhost/assets/css/style.css',
            UriHelper::getBaseUrl('/assets/css/style.css', [
                'HTTPS' => 'on',
                'HTTP_HOST' => 'localhost',
                'SCRIPT_NAME' => '/index.php'
            ], false)
        );
        $this->assertEquals(
            'https://localhost/public/assets/css/style.css',
            UriHelper::getBaseUrl('/assets/css/style.css', [
                'HTTPS' => 'on',
                'HTTP_HOST' => 'localhost',
                'SCRIPT_NAME' => '/public/index.php'
            ], false)
        );
        $this->assertEquals(
            'https://localhost/public/assets/css/style.css',
            UriHelper::getBaseUrl('/assets/css/style.css', [
                'HTTPS' => 'on',
                'SERVER_NAME' => 'localhost',
                'SERVER_PORT' => 443,
                'SCRIPT_NAME' => '/public/index.php'
            ], false)
        );
        $this->assertEquals(
            'https://localhost:3000/public/assets/css/style.css',
            UriHelper::getBaseUrl('/assets/css/style.css', [
                'HTTPS' => 'on',
                'SERVER_NAME' => 'localhost',
                'SERVER_PORT' => 3000,
                'SCRIPT_NAME' => '/public/index.php'
            ], false)
        );
    }

    public function testGetSiteUrl()
    {
        $this->assertEquals(
            'https://localhost:3000/public/index.php/some/path',
            siteUrl('/some/path', [
                'HTTPS' => 'on',
                'SERVER_NAME' => 'localhost',
                'SERVER_PORT' => 3000,
                'SCRIPT_NAME' => '/public/index.php'
            ], false)
        );

        $this->assertEquals(
            'https://www.domain.com:3000/public/index.php/some/path',
            siteUrl('/some/path', [
                'HTTPS' => 'on',
                'SERVER_NAME' => 'localhost',
                'SERVER_PORT' => 3000,
                'SCRIPT_NAME' => 'index.php',
                'HTTP_X_FORWARDED_HOST' => 'www.domain.com',
                'HTTP_X_FORWARDED_PREFIX' => '/public'
            ], false)
        );
    }

    public function testGetPathParams()
    {
        $matches = UriHelper::getPathParams('/a/1/b/2', '/a/{a}/b/{b}');
        // var_dump($matches);
        $this->assertTrue(true);
    }
}
