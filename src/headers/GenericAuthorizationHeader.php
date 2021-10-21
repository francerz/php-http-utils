<?php

namespace Francerz\Http\Utils\Headers;

class GenericAuthorizationHeader extends AbstractAuthorizationHeader
{
    private $type;
    private $credentials;

    public function __construct(string $type, ?string $credentials = null)
    {
        $this->type = $type;
        $this->credentials = $credentials;
    }

    public function withCredentials(string $credentials)
    {
        $new = clone $this;
        $new->credentials = $credentials;
        return $new;
    }

    public function getCredentials(): string
    {
        return $this->credentials;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function __toString()
    {
        return "{$this->type} {$this->credentials}";
    }

    public static function getAuthorizationType(): string
    {
        return '';
    }
}
