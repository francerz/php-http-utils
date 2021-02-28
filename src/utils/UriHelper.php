<?php

namespace Francerz\Http\Utils;

use LogicException;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

class UriHelper
{
    # region Private methods
    private static function mixUrlEncodedParams(string $encoded_string, array $map, $replace = true, $toString = true) : string
    {
        parse_str($encoded_string, $params);
        if ($toString) $map = array_map(function($v) { return (string)$v; }, $map);
        $params = $replace ? array_merge($params, $map) : array_merge($map, $params);
        return http_build_query($params);
    }
    private static function removeUrlEncodedParam(string $encoded_string, string $key, &$value = null) : string
    {
        parse_str($encoded_string, $params);
        if (array_key_exists($key, $params)) {
            $value = $params[$key];
            unset($params[$key]);
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
    public static function withQueryParam(UriInterface $uri, string $key, $value, bool $toString = true) : UriInterface
    {
        return $uri->withQuery(static::mixUrlEncodedParams($uri->getQuery(), [$key => $value], true, $toString));
    }
    public static function withQueryParams(UriInterface $uri, array $params, $replace = true, bool $toString = true) : UriInterface
    {
        return $uri->withQuery(static::mixUrlEncodedParams($uri->getQuery(), $params, $replace, $toString));
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

    /**
     * Copies existant query parameters from one URI to another.
     *
     * @param UriInterface $sourceUri
     * @param UriInterface $destUri
     * @param array $params An array with the parameter keys.
     * Associative array will represent source and target and query names.
     * @return UriInterface
     */
    public static function copyQueryParams(UriInterface $sourceUri, UriInterface $destUri, array $params = []) : UriInterface
    {
        $copies = [];
        foreach ($params as $source => $target) {
            $source = is_numeric($source) ? $target : $source;
            $copies[$target] = UriHelper::getQueryParam($sourceUri, $source);
        }
        
        return UriHelper::withQueryParams($destUri, $copies);
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

    private static function mapReplaceString(string $uri, array $replaces, bool $encode_values = true) : string
    {
        $match = preg_match_all('/\{([a-zA-Z0-9\-\_]+)\}/S', $uri, $matches);
        if (!$match) {
            return $uri;
        }
        $matches = array_unique($matches[1]);
        foreach ($matches as $match) {
            if (!array_key_exists($match, $replaces)) {
                continue;
            }
            $val = $encode_values ? urlencode($replaces[$match]) : $replaces[$match];
            $uri = str_replace('{'.$match.'}', $val, $uri);
        }
        return $uri;
    }

    public static function mapReplace(UriFactoryInterface $uriFactory, $uri, array $replaces, bool $encode_values = true) : UriInterface
    {
        $uriStr = $uri;
        if ($uri instanceof UriInterface) {
            $uriStr = (string) $uri;
        }
        if (!is_string($uriStr)) {
            throw new LogicException(__METHOD__.' $uri argument must be string or UriInterface object');
        }
        $uriStr = static::mapReplaceString($uri, $replaces, $encode_values);
        return $uriFactory->createUri($uriStr);
    }

    public static function getSegments(UriInterface $uri) : array
    {
        $path = $uri->getPath();
        return explode('/', ltrim($path,'/'));
    }
}