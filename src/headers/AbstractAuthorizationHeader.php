<?php

namespace Francerz\Http\Utils\Headers;

abstract class AbstractAuthorizationHeader implements HeaderInterface
{
    abstract public static function getAuthorizationType(): string;
    abstract public function withCredentials(string $credentials);
    abstract public function getCredentials(): string;
    public function getType(): string
    {
        return static::getAuthorizationType();
    }
}
