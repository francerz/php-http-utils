<?php

namespace Francerz\Http\Utils;

use Francerz\Http\Utils\UriHelper;

if (!function_exists(__NAMESPACE__ . '\siteUrl')) {
    function siteUrl(?string $path = null, array $sapiVars = [], bool $cached = false)
    {
        return UriHelper::getSiteUrl($path, $sapiVars, $cached);
    }
}

if (!function_exists(__NAMESPACE__ . '\baseUrl')) {
    function baseUrl(?string $path = null, array $sapiVars = [], bool $cached = false)
    {
        return UriHelper::getBaseUrl($path, $sapiVars, $cached);
    }
}

if (!function_exists(__NAMESPACE__ . '\currentUrl')) {
    function currentUrl(array $sapiVars = [], bool $cached = false)
    {
        return UriHelper::getCurrentString($sapiVars, $cached);
    }
}
