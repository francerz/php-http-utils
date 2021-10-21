<?php

namespace Francerz\Http\Utils\Exceptions;

use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class ServerErrorException extends Exception implements ResponseExceptionInterface
{
    private $request;
    private $response;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        string $message = '',
        int $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->request = $request;
        $this->response = $response;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
