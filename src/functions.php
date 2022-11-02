<?php

namespace Francerz\Http\Utils;

use Francerz\Http\Utils\UriHelper;

if (!function_exists('\Francerz\Http\Utils\siteUrl')) {
    function siteUrl(?string $path = null, array $sapiVars = [], bool $cached = true)
    {
        return UriHelper::getSiteUrl($path, $sapiVars, $cached);
    }
}

if (!function_exists('\Francerz\Http\Utils\baseUrl')) {
    function baseUrl(?string $path = null, array $sapiVars = [], bool $cached = true)
    {
        return UriHelper::getBaseUrl($path, $sapiVars, $cached);
    }
}
