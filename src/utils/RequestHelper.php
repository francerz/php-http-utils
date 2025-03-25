<?php

namespace Francerz\Http\Utils;

use Psr\Http\Message\RequestInterface;

class RequestHelper
{
    public static function stringify(RequestInterface $request)
    {
        $path = $request->getUri()->getPath();
        if (!empty($queryString = $request->getUri()->getQuery())) {
            $path .= '?' . $queryString;
        }
        $string = sprintf(
            "%s %s HTTP/%s\n",
            $request->getMethod(),
            $path,
            $request->getProtocolVersion()
        );
        foreach ($request->getHeaders() as $name => $values) {
            $string .= sprintf("%s: %s\n", $name, implode(', ', $values));
        }
        $string .= "\n" . $request->getBody();
        return $string;
    }
}
