<?php

namespace Francerz\Http\Utils;

use Francerz\Http\Utils\UriHelper;

if (!function_exists('\Francerz\Http\Utils\siteUrl')) {
    function siteUrl(?string $path = null, array $sapiVars = [])
    {
        return UriHelper::getSiteUrl($path, $sapiVars);
    }
}

if (!function_exists('\Francerz\Http\Utils\baseUrl')) {
    function baseUrl(?string $path = null, array $sapiVars = [])
    {
        return UriHelper::getBaseUrl($path, $sapiVars);
    }
}
