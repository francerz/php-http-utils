<?php

namespace Francerz\Http\Tools;

use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

class UriHelper
{
    # region Private methods
    private static function mixUrlEncodedParams(string $encoded_string, array $map, $replace = true) : string
    {
        parse_str($encoded_string, $params);
        $params = $replace ? array_merge($params, $map) : array_merge($map, $params);
        return http_build_query($params);
    }
    private static function removeUrlEncodedParam(string $encoded_string, string $key, &$value = null) : string
    {
        parse_str($encoded_string, $params);
        if (array_key_exists($key, $params)) {
            $value = $params[$key];
            unset($params);
            return http_build_query($params);
        }
        return $encoded_string;
    }

    private static function startWithSlash(?string $path) : string
    {
        if (is_null($path)) {
            return '/';
        }
        return strlen($path) === 0 || $path[0] !== '/' ? '/'.$path : $path;
    }
    private static function removeLastSlash(?string $path) : string
    {
        if (is_null($path)) {
            return '';
        }
        return substr($path, -1) === '/' ? substr($path, 0, -1) : $path;
    }
    #endregion

    #region QueryParams
    public static function withQueryParam(UriInterface $uri, string $key, $value) : UriInterface
    {
        return $uri->withQuery(static::mixUrlEncodedParams($uri->getQuery(), [$key => $value]));
    }
    public static function withQueryParams(UriInterface $uri, array $params, $replace = true) : UriInterface
    {
        return $uri->withQuery(static::mixUrlEncodedParams($uri->getQuery(), $params, $replace));
    }
    public static function withoutQueryParam(UriInterface $uri, string $key, &$value = null) : UriInterface
    {
        return $uri->withQuery(static::removeUrlEncodedParam($uri->getQuery(), $key, $value));
    }
    public static function getQueryParams(UriInterface $uri) : array
    {
        parse_str($uri->getQuery(), $params);
        if (is_null($params)) {
            return [];
        }
        return $params;
    }
    public static function getQueryParam(UriInterface $uri, string $key) : ?string
    {
        $params = static::getQueryParams($uri);
        if (!array_key_exists($key, $params)) {
            return null;
        }
        return $params[$key];
    }
    #endregion

    #region FragmentParams
    public static function withFragmentParam(UriInterface $uri, string $key, $value) : UriInterface
    {
        return $uri->withFragment(static::mixUrlEncodedParams($uri->getFragment(), [$key => $value]));
    }
    public static function withFragmentParams(UriInterface $uri, array $params, $replace = true) : UriInterface
    {
        return $uri->withFragment(static::mixUrlEncodedParams($uri->getFragment(), $params, $replace));
    }
    public static function withoutFragmentParam(UriInterface $uri, string $key, &$value = null) : UriInterface
    {
        return $uri->withFragment(static::removeUrlEncodedParam($uri->getFragment(), $key, $value));
    }
    public static function getFragmentParams(UriInterface $uri) : array
    {
        parse_str($uri->getFragment(), $params);
        if (is_null($params)) {
            return [];
        }
        return $params;
    }
    public static function getFragmentParam(UriInterface $uri, string $key) : ?string
    {
        $params = static::getFragmentParams($uri);
        if (!array_key_exists($key, $params)) {
            return null;
        }
        return $params[$key];
    }
    #endregion

    #region Path
    public static function appendPath(UriInterface $uri, string $path) : UriInterface
    {
        $path = static::startWithSlash($path);
        $prepath = static::removeLastSlash($uri->getPath());

        return $uri->withPath($prepath.$path);
    }
    public static function prependPath(UriInterface $uri, string $path) : UriInterface
    {
        $path = static::removeLastSlash($path);
        $postpath = static::startWithSlash($uri->getPath());

        return $uri->withPath($path.$postpath);
    }
    #endregion

    public static function getCurrent(UriFactoryInterface $uriFactory) : UriInterface
    {
        $uri = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https': 'http';
        $uri.= '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        return $uriFactory->createUri($uri);
    }
}