<?php

namespace Francerz\Http\Utils\Exceptions;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ResponseExceptionInterface
{
    public function getRequest(): RequestInterface;
    public function getResponse(): ResponseInterface;
}
