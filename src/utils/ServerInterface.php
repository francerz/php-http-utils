<?php

namespace Francerz\Http\Utils;

use Psr\Http\Message\ResponseInterface;

interface ServerInterface
{
    public function emitResponse(ResponseInterface $response);
}
