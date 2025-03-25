<?php

namespace Francerz\HttpUtils\Dev;

use Francerz\Http\Utils\UriHelper;
use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    private $scheme;
    private $user;
    private $password;
    private $host;
    private $port;
    private $path;
    private $query;
    private $fragment;

    public function __construct(string $uri)
    {
        $uriParts = parse_url($uri);
        $this->scheme = $uriParts['scheme'];
        $this->user = $uriParts['user'] ?? '';
        $this->password = $uriParts['pass'] ?? null;
        $this->host = $uriParts['host'] ?? '';
        $this->port = $uriParts['port'] ?? null;
        $this->path = $uriParts['path'] ?? '/';
        $this->query = $uriParts['query'] ?? '';
        $this->fragment = $uriParts['fragment'] ?? '';
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): string
    {
        $authority = $this->host;
        if (!empty($this->user)) {
            $authority = $this->getUserInfo() . '@' . $authority;
        }
        if (isset($this->port)) {
            $authority .= ':' . $this->port;
        }
        return $authority;
    }

    public function getUserInfo(): string
    {
        $userInfo = $this->user;
        if (isset($this->password)) {
            $userInfo = ':' . $this->password;
        }
        return $userInfo;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getFragment(): string
    {
        return $this->fragment;
    }

    public function withScheme(string $scheme): UriInterface
    {
        $clone = clone $this;
        $clone->scheme = $scheme;
        return $clone;
    }

    public function withUserInfo(string $user, ?string $password = null): UriInterface
    {
        $clone = clone $this;
        $clone->user = $user;
        $clone->password = $password;
        return $clone;
    }

    public function withHost(string $host): UriInterface
    {
        $clone = clone $this;
        $clone->host = $host;
        return $clone;
    }

    public function withPort(?int $port): UriInterface
    {
        $clone = clone $this;
        $clone->port = $port;
        return $clone;
    }

    public function withPath(string $path): UriInterface
    {
        $clone = clone $this;
        $clone->path = $path;
        return $clone;
    }

    public function withQuery(string $query): UriInterface
    {
        $clone = clone $this;
        $clone->query = $query;
        return $clone;
    }

    public function withFragment(string $fragment): UriInterface
    {
        $clone = clone $this;
        $clone->fragment = $fragment;
        return $clone;
    }

    public function __toString(): string
    {
        return UriHelper::buildStringFromParts([
            'scheme' => $this->scheme,
            'user' => $this->user,
            'pass' => $this->password,
            'host' => $this->host,
            'port' => $this->port,
            'path' => $this->path,
            'query' => $this->query,
            'fragment' => $this->fragment
        ]);
    }
}
