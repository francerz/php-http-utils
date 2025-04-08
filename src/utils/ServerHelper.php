<?php

namespace Francerz\Http\Utils;

use Psr\Http\Message\ServerRequestInterface;

class ServerHelper
{
    private $sapi;

    public function __construct(?array $serverParams = null)
    {
        $this->sapi = $serverParams ?? $_SERVER;
    }

    public static function fromRequest(ServerRequestInterface $request)
    {
        return new static($request->getServerParams());
    }

    public function getSapiVars()
    {
        return $this->sapi;
    }
}
