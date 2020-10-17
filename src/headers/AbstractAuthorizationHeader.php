<?php

namespace Francerz\Http\Headers;

abstract class AbstractAuthorizationHeader implements HeaderInterface
{
    public abstract function withCredentials(string $credentials);
    public static abstract function getAuthorizationType() : string;
}