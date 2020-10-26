<?php

namespace Francerz\Http\Utils\Headers;

abstract class AbstractAuthorizationHeader implements HeaderInterface
{
    public static abstract function getAuthorizationType() : string;
    public abstract function withCredentials(string $credentials);
    public abstract function getCredentials() : string;
    public function getType() : string
    {
        return static::getAuthorizationType();
    }
}