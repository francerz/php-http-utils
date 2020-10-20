<?php

namespace Francerz\Http\Tools;

use Francerz\Http\Headers\AbstractAuthorizationHeader;
use Francerz\Http\Headers\BasicAuthorizationHeader;
use Francerz\Http\Headers\BearerAuthorizationHeader;
use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;

class MessageHelper
{
    private static $httpFactoryManager;

    public static function setHttpFactoryManager(HttpFactoryManager $factories)
    {
        static::$httpFactoryManager = $factories;
    }

    public static function getCurrentRequest() : RequestInterface
    {
        if (!isset(static::$httpFactoryManager)) {
            throw new \Exception(sprintf(
                'Method %s::%s requires you setHttpFactoryManager on %s.',
                __CLASS__, __METHOD__, __CLASS__
            ));
        }

        $requestFactory = static::$httpFactoryManager->getRequestFactory();
        $uriFactory     = static::$httpFactoryManager->getUriFactory();
        $streamFactory  = static::$httpFactoryManager->getStreamFactory();

        // Retrieves current request elements
        $sp = $_SERVER['SERVER_PROTOCOL'];
        $sp = substr($sp, strpos($sp, '/') + 1);

        $uri = UriHelper::getCurrent($uriFactory);
        $method = $_SERVER['REQUEST_METHOD'];


        $content = file_get_contents('php://input');
        $content = is_string($content) ? $content : '';

        // Builds request with factory
        $request = $requestFactory
            ->createRequest($method, $uri)
            ->withProtocolVersion($sp)
            ->withBody($streamFactory->createStream($content));

        $headers = getallheaders();
        foreach ($headers as $hname => $hcontent) {
            $request = $request->withHeader($hname, preg_split('/(,\\s*)/', $hcontent));
        }

        return $request;
    }

    private static $authenticationSchemesClasses;

    public static function setAuthenticationSchemes(array $authenticationSchemesClasses)
    {
        array_walk($authenticationSchemesClasses, function($v) {
            if (!class_exists($v)) {
                throw new InvalidArgumentException("Class $v does not exists.");
            }
            if (!is_subclass_of($v, AbstractAuthorizationHeader::class)) {
                throw new InvalidArgumentException(
                    'Authentication Schemes classes MUST extends from '.
                    AbstractAuthorizationHeader::class
                );
            }
        });
        $types = array_map(function(AbstractAuthorizationHeader $v) {
            return $v::getAuthorizationType();
        }, $authenticationSchemesClasses);
        
        static::$authenticationSchemesClasses = array_combine($types, $authenticationSchemesClasses);
    }

    public static function getFirstAuthorizationHeader(MessageInterface $message) : ?AbstractAuthorizationHeader
    {
        $header = $message->getHeader('Authorization');

        if (empty($header)) {
            return null;
        }

        $header = current($header);

        $wsp = strpos($header, ' ');
        $type = ucfirst(strtolower(substr($header, 0, $wsp)));
        $content = substr($header, $wsp + 1);

        if (!array_key_exists($type, static::$authenticationSchemesClasses)) {
            return null;
        }
        $authSch = static::$authenticationSchemesClasses[$type];

        $authHeader = new $authSch();
        return $authHeader->withCredentials($content);
    }

    public static function getContent(MessageInterface $message)
    {
        $body = $message->getBody();
        $type = $message->getHeader('Content-Type');

        if (empty($type)) {
            return (string) $body;
        }

        $parser = BodyParserHandler::find($type[0]);
        if (empty($parser)) {
            return (string) $body;
        }

        return $parser->parse($body, $type[0]);
    }

    public static function withContent(MessageInterface $message, string $mediaType, $content) : MessageInterface
    {
        if (!isset(static::$httpFactoryManager)) {
            throw new \Exception(sprintf(
                'Method %s::%s requires you setHttpFactoryManager on %s.',
                __CLASS__, __METHOD__, __CLASS__
            ));
        }

        $parser = BodyParserHandler::find($mediaType);
        $streamFactory = static::$httpFactoryManager->getStreamFactory();

        if (isset($parser)) {
            $body = $parser->unparse($streamFactory, $content, $mediaType);
        } else {
            $body = $streamFactory->createStream($content);
        }

        return $message
            ->withBody($body)
            ->withHeader('Content-Type', $mediaType);
    }
}

MessageHelper::setAuthenticationSchemes(array(
    BasicAuthorizationHeader::class,
    BearerAuthorizationHeader::class
));