<?php

namespace Francerz\Http\Utils;

use LogicException;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

class UriHelper
{
    # region Private methods
    private static function mixUrlEncodedParams(
        string $encoded_string,
        array $map,
        bool $replace = true,
        bool $toString = true
    ): string {
        parse_str($encoded_string, $params);
        if ($toString) {
            $map = array_map(function ($v) {
                if (is_object($v)) {
                    return (string)$v;
                }
                return $v;
            }, $map);
        }
        $params = $replace ? array_merge($params, $map) : array_merge($map, $params);
        return http_build_query($params);
    }
    private static function removeUrlEncodedParam(string $encoded_string, string $key, &$value = null): string
    {
        parse_str($encoded_string, $params);
        if (array_key_exists($key, $params)) {
            $value = $params[$key];
            unset($params[$key]);
            return http_build_query($params);
        }
        return $encoded_string;
    }

    private static function startWithSlash(?string $path): string
    {
        if (is_null($path)) {
            return '/';
        }
        return strlen($path) === 0 || $path[0] !== '/' ? '/' . $path : $path;
    }
    private static function removeLastSlash(?string $path): string
    {
        if (is_null($path)) {
            return '';
        }
        return substr($path, -1) === '/' ? substr($path, 0, -1) : $path;
    }
    #endregion

    #region QueryParams
    public static function withQueryParam(UriInterface $uri, string $key, $value, bool $toString = true): UriInterface
    {
        return $uri->withQuery(static::mixUrlEncodedParams($uri->getQuery(), [$key => $value], true, $toString));
    }
    public static function withQueryParams(UriInterface $uri, array $params, $replace = true, bool $toString = true): UriInterface
    {
        return $uri->withQuery(static::mixUrlEncodedParams($uri->getQuery(), $params, $replace, $toString));
    }
    public static function withoutQueryParam(UriInterface $uri, string $key, &$value = null): UriInterface
    {
        return $uri->withQuery(static::removeUrlEncodedParam($uri->getQuery(), $key, $value));
    }
    public static function getQueryParams(UriInterface $uri): array
    {
        parse_str($uri->getQuery(), $params);
        if (is_null($params)) {
            return [];
        }
        return $params;
    }
    public static function getQueryParam(UriInterface $uri, string $key): ?string
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
    public static function copyQueryParams(UriInterface $sourceUri, UriInterface $destUri, array $params = []): UriInterface
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
    public static function withFragmentParam(UriInterface $uri, string $key, $value): UriInterface
    {
        return $uri->withFragment(static::mixUrlEncodedParams($uri->getFragment(), [$key => $value]));
    }
    public static function withFragmentParams(UriInterface $uri, array $params, $replace = true): UriInterface
    {
        return $uri->withFragment(static::mixUrlEncodedParams($uri->getFragment(), $params, $replace));
    }
    public static function withoutFragmentParam(UriInterface $uri, string $key, &$value = null): UriInterface
    {
        return $uri->withFragment(static::removeUrlEncodedParam($uri->getFragment(), $key, $value));
    }
    public static function getFragmentParams(UriInterface $uri): array
    {
        parse_str($uri->getFragment(), $params);
        if (is_null($params)) {
            return [];
        }
        return $params;
    }
    public static function getFragmentParam(UriInterface $uri, string $key): ?string
    {
        $params = static::getFragmentParams($uri);
        if (!array_key_exists($key, $params)) {
            return null;
        }
        return $params[$key];
    }
    #endregion

    #region Path
    public static function appendPath(UriInterface $uri, string ...$segments): UriInterface
    {
        $postpath = '';
        foreach ($segments as $s) {
            $postpath .= static::startWithSlash($s);
        }
        $prepath = static::removeLastSlash($uri->getPath());

        return $uri->withPath($prepath . $postpath);
    }

    public static function prependPath(UriInterface $uri, string ...$segments): UriInterface
    {
        $prepath = '';
        foreach ($segments as $s) {
            $prepath .= static::removeLastSlash($s);
        }
        $postpath = static::startWithSlash($uri->getPath());

        return $uri->withPath($prepath . $postpath);
    }

    /**
     * @param UriInterface $uri
     * @param string $pattern
     * @return array
     */
    public static function getPathParams(string $uri, string $pattern)
    {
        $pattern = '/([-_\w\d])/';
        $match = preg_match_all($pattern, $uri, $matches);
        return $matches;
    }
    #endregion

    /**
     * Retrieves path info from URL.
     *
     * Based upon Slim Framework
     *
     * @param ?string $requestUri
     * @param ?string $scriptName
     * @return string
     */
    public static function getPathInfo(?string $requestUri = null, ?string $scriptName = null): string
    {
        $scriptName = $scriptName ?? $_SERVER['SCRIPT_NAME'];
        $requestUri = $requestUri ?? $_SERVER['REQUEST_URI'];

        $pathInfo = $requestUri;

        // Removing Script name
        if (strpos($requestUri, $scriptName) === 0) {
            // Remove Script file from Request URI
            $pathInfo = substr_replace($requestUri, '', 0, strlen($scriptName));
        } elseif (strpos($requestUri, ($scriptDir = dirname($scriptName))) === 0) {
            // Remove Script directory from Request URI (mod_rewrite)
            $pathInfo = substr_replace($requestUri, '', 0, strlen($scriptDir));
        }

        // Removing Query String
        if (($queryPos = strpos($pathInfo, '?')) !== false) {
            $pathInfo = substr_replace($pathInfo, '', $queryPos);
        }

        return '/' . ltrim($pathInfo, '/');
    }

    public static function getSiteUrl(?string $path = null, array $sapiVars = [], bool $cached = false)
    {
        $sapiVars = array_merge($_SERVER, $sapiVars);
        $sapiVars['REQUEST_URI'] = $sapiVars['SCRIPT_NAME'] ?? '';
        $uri = static::getCurrentString($sapiVars, $cached);
        if (isset($path)) {
            $uri = rtrim($uri, '/') . '/' . ltrim($path, '/');
        }
        return $uri;
    }

    public static function getBaseUrl(?string $path = null, array $sapiVars = [], bool $cached = false)
    {
        $sapiVars = array_merge($_SERVER, $sapiVars);
        $scriptName = $sapiVars['SCRIPT_NAME'] ?? '';
        $sapiVars['SCRIPT_NAME'] = substr_replace($scriptName, '', strrpos($scriptName, '/'));
        return static::getSiteUrl($path, $sapiVars, $cached);
    }

    public static function getCurrentString(array $sapiVars = [], bool $cached = false)
    {
        static $uri;
        if ($cached && isset($uri)) {
            return $uri;
        }

        $sapiVars = array_merge($_SERVER, $sapiVars);

        $scheme = 'http';
        $defaultPort = 80;
        if (!empty($sapiVars['HTTPS']) && $sapiVars['HTTPS'] != 'off') {
            $scheme = 'https';
            $defaultPort = 443;
        }

        $host = $sapiVars['HTTP_HOST'] ?? null;
        if (!isset($host)) {
            $host = $sapiVars['SERVER_NAME'];
            // Detecting port and comparing with default
            if (isset($sapiVars['SERVER_PORT']) && ($port = $sapiVars['SERVER_PORT']) !== $defaultPort) {
                $host .= ":{$port}";
            }
        }

        $uri = "{$scheme}://{$host}";
        $uri = rtrim($uri, '/') . '/' . ltrim($sapiVars['REQUEST_URI'], '/');
        return $uri;
    }

    public static function getCurrent(UriFactoryInterface $uriFactory): UriInterface
    {
        return $uriFactory
            ->createUri(static::getCurrentString())
            ->withQuery($_SERVER['QUERY_STRING'] ?? '');
    }

    private static function mapReplaceString(string $uri, array $replaces, bool $encode_values = true): string
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
            $uri = str_replace('{' . $match . '}', $val, $uri);
        }
        return $uri;
    }

    public static function mapReplace(
        UriFactoryInterface $uriFactory,
        $uri,
        array $replaces,
        bool $encode_values = true
    ): UriInterface {
        $uriStr = $uri;
        if ($uri instanceof UriInterface) {
            $uriStr = (string) $uri;
        }
        if (!is_string($uriStr)) {
            throw new LogicException(__METHOD__ . ' $uri argument must be string or UriInterface object');
        }
        $uriStr = static::mapReplaceString($uri, $replaces, $encode_values);
        return $uriFactory->createUri($uriStr);
    }

    public static function getSegments(UriInterface $uri): array
    {
        $path = $uri->getPath();
        return explode('/', ltrim($path, '/'));
    }

    public static function isValid($uri): bool
    {
        if (is_object($uri) && method_exists($uri, '__toString')) {
            $uri = (string) $uri;
        }
        return filter_var($uri, FILTER_VALIDATE_URL);
    }

    public static function getUser(UriInterface $uri): string
    {
        $explode = explode(':', $uri->getUserInfo());
        return $explode[0];
    }

    public static function getPassword(UriInterface $uri): string
    {
        $explode = explode(':', $uri->getUserInfo());
        if (isset($explode[1])) {
            return $explode[1];
        }
        return null;
    }

    public static function base64Encode(string $data)
    {
        return strtr(base64_encode($data), ['+' => '-', '/' => '_', '=' => '']);
    }

    public static function base64Decode(string $base64, bool $strict = false)
    {
        return base64_decode(
            strtr($base64, '-_', '+/') .
            str_repeat('=', - strlen($base64) & 3),
            $strict
        );
    }
}
