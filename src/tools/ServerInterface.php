<?php

namespace Francerz\Http\Tools;

use Psr\Http\Message\ResponseInterface;

interface ServerInterface
{
    public function emitResponse(ResponseInterface $response);
}