<?php

namespace Francerz\Http\Utils\Headers;

class BearerAuthorizationHeader extends AbstractAuthorizationHeader
{
    private $token;

    public function __construct(string $token = '')
    {
        $this->token = $token;
    }
    public function withCredentials(string $credentials)
    {
        $new = clone $this;
        $new->token = $credentials;
        return $new;
    }
    public function getCredentials(): string
    {
        return $this->token;
    }
    public function withToken(string $token)
    {
        $new = clone $this;
        $new->token = $token;
        return $new;
    }
    public function getToken(): string
    {
        return $this->token;
    }

    public static function getAuthorizationType(): string
    {
        return 'Bearer';
    }
    public function __toString()
    {
        return 'Bearer ' . $this->token;
    }
}
