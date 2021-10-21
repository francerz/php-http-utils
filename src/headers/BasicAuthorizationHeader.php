<?php

namespace Francerz\Http\Utils\Headers;

class BasicAuthorizationHeader extends AbstractAuthorizationHeader
{
    private $user;
    private $password;

    public function __construct(string $user = '', string $password = '')
    {
        $this->user = $user;
        $this->password = $password;
    }

    public function withCredentials(string $credentials)
    {
        $decoded = base64_decode($credentials);
        $parts = explode(':', $decoded);

        $new = clone $this;
        $new->user = $parts[0];
        $new->password = $parts[1] ?? '';
        return $new;
    }
    public function getCredentials(): string
    {
        return $this->user . ':' . $this->password;
    }

    public function withUser(string $user)
    {
        $new = clone $this;
        $new->user = $user;
        return $new;
    }
    public function getUser(): string
    {
        return $this->user;
    }
    public function withPassword(string $password)
    {
        $new = clone $this;
        $new->password = $password;
        return $new;
    }
    public function getPassword(): string
    {
        return $this->password;
    }

    public static function getAuthorizationType(): string
    {
        return 'Basic';
    }
    public function __toString()
    {
        return 'Basic ' . base64_encode($this->user . ':' . $this->password);
    }
}
