<?php

use Francerz\Http\Tools\UriHelper;
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
}