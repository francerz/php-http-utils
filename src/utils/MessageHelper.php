<?php

namespace Francerz\Http\Utils;

use Francerz\Http\Utils\Constants\StatusCodes;
use Francerz\Http\Utils\Headers\AbstractAuthorizationHeader;
use Francerz\Http\Utils\Headers\BasicAuthorizationHeader;
use Francerz\Http\Utils\Headers\BearerAuthorizationHeader;
use InvalidArgumentException;
use LogicException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * @deprecated v0.2.24
 */
class MessageHelper
{
    private static $httpFactoryManager;

    public static function setHttpFactoryManager(HttpFactoryManager $factories)
    {
        static::$httpFactoryManager = $factories;
    }

    private static function checkFactoryManager($method)
    {
        if (!isset(static::$httpFactoryManager)) {
            throw new LogicException(sprintf(
                'Method %s requires assign setHttpFactoryManager',
                $method
            ));
        }
    }

    public static function getCurrentRequest(): ServerRequestInterface
    {
        static::checkFactoryManager(__METHOD__);

        $requestFactory = static::$httpFactoryManager->getServerRequestFactory();
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
            ->createServerRequest($method, $uri, $_SERVER)
            ->withProtocolVersion($sp)
            ->withBody($streamFactory->createStream($content));

        $headers = getallheaders();
        foreach ($headers as $hname => $hcontent) {
            $request = $request->withHeader($hname, preg_split('/(,\\s*)/', $hcontent));
        }

        return $request;
    }

    private static $authenticationSchemeClasses;

    public static function setAuthenticationSchemes(array $authenticationSchemeClasses)
    {
        foreach ($authenticationSchemeClasses as $class) {
            static::addAuthenticationScheme($class);
        }
    }

    public static function addAuthenticationScheme(string $authenticationSchemeClass)
    {
        if (!class_exists($authenticationSchemeClass)) {
            throw new InvalidArgumentException(sprintf('Class %s does not exists.', $authenticationSchemeClass));
        }
        if (!is_subclass_of($authenticationSchemeClass, AbstractAuthorizationHeader::class)) {
            throw new InvalidArgumentException(
                'Authentication Scheme class MUST extend from ' .
                    AbstractAuthorizationHeader::class
            );
        }
        $type = $authenticationSchemeClass::getAuthorizationType();
        static::$authenticationSchemeClasses[$type] = $authenticationSchemeClass;
    }

    public static function getFirstAuthorizationHeader(MessageInterface $message): ?AbstractAuthorizationHeader
    {
        $header = $message->getHeader('Authorization');

        if (empty($header)) {
            return null;
        }

        $header = current($header);

        $wsp = strpos($header, ' ');
        $type = ucfirst(strtolower(substr($header, 0, $wsp)));
        $content = substr($header, $wsp + 1);

        if (!array_key_exists($type, static::$authenticationSchemeClasses)) {
            return null;
        }
        $authSch = static::$authenticationSchemeClasses[$type];

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

    public static function withContent(MessageInterface $message, string $mediaType, $content): MessageInterface
    {
        static::checkFactoryManager(__METHOD__);

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

    public static function makeRedirect($location, int $code = StatusCodes::REDIRECT_TEMPORARY_REDIRECT)
    {
        static::checkFactoryManager(__METHOD__);
        $responseFactory = static::$httpFactoryManager->getResponseFactory();

        if ($location instanceof UriInterface) {
            $location = (string)$location;
        }

        return $responseFactory
            ->createResponse($code)
            ->withHeader('Location', $location);
    }

    public static function createResponseFromFile($filepath, $filename = null, $attachment = false): ResponseInterface
    {
        static::checkFactoryManager(__METHOD__);
        $responseFactory = static::$httpFactoryManager->getResponseFactory();
        $streamFactory = static::$httpFactoryManager->getStreamFactory();

        $response = $responseFactory
            ->createResponse()
            ->withHeader('Content-Type', mime_content_type($filepath))
            ->withBody($streamFactory->createStreamFromFile($filepath));
        if (isset($filename)) {
            $disposition = $attachment ? 'attachment' : 'inline';
            $response = $response->withHeader('Content-Disposition', "{$disposition};filename=\"{$filename}\"");
        }
        return $response;
    }

    public static function isInfo(ResponseInterface $response): bool
    {
        return $response->getStatusCode() >= 100 && $response->getStatusCode() < 200;
    }
    public static function isSuccess(ResponseInterface $response): bool
    {
        return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
    }
    public static function isRedirect(ResponseInterface $response): bool
    {
        return $response->getStatusCode() >= 300 && $response->getStatusCode() < 400;
    }
    public static function isClientError(ResponseInterface $response): bool
    {
        return $response->getStatusCode() >= 400 && $response->getStatusCode() < 500;
    }
    public static function isServerError(ResponseInterface $response): bool
    {
        return $response->getStatusCode() >= 500;
    }
    public static function isError(ResponseInterface $response): bool
    {
        return $response->getStatusCode() >= 400;
    }
}

MessageHelper::setAuthenticationSchemes(array(
    BasicAuthorizationHeader::class,
    BearerAuthorizationHeader::class
));
