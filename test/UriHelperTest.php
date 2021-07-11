<?php

use Francerz\Http\Utils\UriHelper;
use PHPUnit\Framework\TestCase;

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
        $uri = static::callPrivateStatic(UriHelper::class, 'mapReplaceString',
            'https://example.com/collection/{id}/{id2}',
            ['id'=>20, 'id2'=>30]
        );

        $this->assertEquals('https://example.com/collection/20/30', $uri);
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
}