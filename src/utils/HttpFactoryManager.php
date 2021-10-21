<?php

namespace Francerz\Http\Utils;

use LogicException;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class HttpFactoryManager
{
    private $requestFactory;
    private $responseFactory;
    private $serverRequestFactory;
    private $streamFactory;
    private $uploadedFileFactory;
    private $uriFactory;

    public function __construct(...$defaultFactories)
    {
        foreach ($defaultFactories as $f) {
            $this->setMatchingFactories($f);
        }
    }

    public function setMatchingFactories($factoryObject)
    {
        if ($factoryObject instanceof  RequestFactoryInterface) {
            $this->setRequestFactory($factoryObject);
        }
        if ($factoryObject instanceof ResponseFactoryInterface) {
            $this->setResponseFactory($factoryObject);
        }
        if ($factoryObject instanceof ServerRequestFactoryInterface) {
            $this->setServerRequestFactory($factoryObject);
        }
        if ($factoryObject instanceof StreamFactoryInterface) {
            $this->setStreamFactory($factoryObject);
        }
        if ($factoryObject instanceof UploadedFileFactoryInterface) {
            $this->setUploadedFileFactory($factoryObject);
        }
        if ($factoryObject instanceof UriFactoryInterface) {
            $this->setUriFactory($factoryObject);
        }
    }

    public function setRequestFactory(RequestFactoryInterface $requestFactory)
    {
        $this->requestFactory = $requestFactory;
    }
    public function getRequestFactory(): RequestFactoryInterface
    {
        if (!isset($this->requestFactory)) {
            throw new LogicException("RequestFactory is not set.");
        }
        return $this->requestFactory;
    }
    public function setResponseFactory(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }
    public function getResponseFactory(): ResponseFactoryInterface
    {
        if (!isset($this->responseFactory)) {
            throw new LogicException("ResponseFactory is not set.");
        }
        return $this->responseFactory;
    }
    public function setServerRequestFactory(ServerRequestFactoryInterface $serverRequestFactory)
    {
        $this->serverRequestFactory = $serverRequestFactory;
    }
    public function getServerRequestFactory(): ServerRequestFactoryInterface
    {
        if (!isset($this->serverRequestFactory)) {
            throw new LogicException("ServerRequestFactory is not set.");
        }
        return $this->serverRequestFactory;
    }
    public function setStreamFactory(StreamFactoryInterface $streamFactory)
    {
        $this->streamFactory = $streamFactory;
    }
    public function getStreamFactory(): StreamFactoryInterface
    {
        if (!isset($this->streamFactory)) {
            throw new LogicException("StreamFactory is not set.");
        }
        return $this->streamFactory;
    }
    public function setUploadedFileFactory(UploadedFileFactoryInterface $uploadedFileFactory)
    {
        $this->uploadedFileFactory = $uploadedFileFactory;
    }
    public function getUploadedFileFactory(): UploadedFileFactoryInterface
    {
        if (!isset($this->uploadedFileFactory)) {
            throw new LogicException("UploadedFileFactory is not set.");
        }
        return $this->uploadedFileFactory;
    }
    public function setUriFactory(UriFactoryInterface $uriFactory)
    {
        $this->uriFactory = $uriFactory;
    }
    public function getUriFactory(): UriFactoryInterface
    {
        if (!isset($this->uriFactory)) {
            throw new LogicException("UriFactory is not set.");
        }
        return $this->uriFactory;
    }
}
