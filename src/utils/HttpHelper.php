<?php

namespace Francerz\Http\Utils;

use Fig\Http\Message\StatusCodeInterface;
use Francerz\Http\Utils\Exceptions\UploadFileExceedsFormMaxSizeException;
use Francerz\Http\Utils\Exceptions\UploadFileExceedsIniMaxSizeException;
use Francerz\Http\Utils\Exceptions\UploadFileFailedWriteException;
use Francerz\Http\Utils\Exceptions\UploadFileMissedException;
use Francerz\Http\Utils\Exceptions\UploadFileMissingTempFolderException;
use Francerz\Http\Utils\Exceptions\UploadFilePartialException;
use Francerz\Http\Utils\Exceptions\UploadFileStoppedByExtensionException;
use Francerz\Http\Utils\Headers\AbstractAuthorizationHeader;
use Francerz\Http\Utils\Headers\BasicAuthorizationHeader;
use Francerz\Http\Utils\Headers\BearerAuthorizationHeader;
use Francerz\Http\Utils\Headers\GenericAuthorizationHeader;
use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

class HttpHelper
{
    private $hfm;

    public function __construct(HttpFactoryManager $hfm)
    {
        $this->hfm = $hfm;
    }

    public function getHttpFactoryManager()
    {
        return $this->hfm;
    }

    public function withHttpFactoryManager(HttpFactoryManager $hfm)
    {
        $new = clone $this;
        $new->hfm = $hfm;
        return $new;
    }

    public function getCurrentRequest() : ServerRequestInterface
    {
        $requestFactory = $this->hfm->getServerRequestFactory();
        $uriFactory     = $this->hfm->getUriFactory();
        $streamFactory  = $this->hfm->getStreamFactory();

        $sp = $_SERVER['SERVER_PROTOCOL'];
        $sp = substr($sp, strpos($sp, '/') + 1);

        $uri = UriHelper::getCurrent($uriFactory);
        $method = $_SERVER['REQUEST_METHOD'];
        $body = $streamFactory->createStreamFromFile('php://input');

        $request = $requestFactory
            ->createServerRequest($method, $uri, $_SERVER)
            ->withProtocolVersion($sp)
            ->withBody($body);
        
        $headers = getallheaders();
        foreach ($headers as $hname => $hcontent) {
            $request = $request->withHeader($hname, preg_split('/(,\\s*)/', $hcontent));
        }

        return $request;
    }

    public function withContent(MessageInterface $message, string $mediaType, $content)
    {
        $parser = BodyParserHandler::find($mediaType);
        $streamFactory = $this->hfm->getStreamFactory();

        if (isset($parser)) {
            $body = $parser->unparse($streamFactory, $content, $mediaType);
        } else {
            $body = $streamFactory->createStream($content);
        }

        return $message
            ->withBody($body)
            ->withHeader('Content-Type', $mediaType);
    }

    public function makeRedirect($location, int $code = StatusCodeInterface::STATUS_TEMPORARY_REDIRECT)
    {
        $responseFactory = $this->hfm->getResponseFactory();

        if ($location instanceof UriInterface) {
            $location = (string)$location;
        }

        return $responseFactory
            ->createResponse($code)
            ->withHeader('Location', $location);
    }

    public function createResponseFromFile($filepath, $filename = null, bool $attachment = false)
    {
        $responseFactory = $this->hfm->getResponseFactory();
        $streamFactory = $this->hfm->getStreamFactory();

        $response = $responseFactory
            ->createResponse()
            ->withHeader('Content-Type', mime_content_type($filepath))
            ->withBody($streamFactory->createStreamFromFile($filepath));
        
        $disposition = $attachment ? 'attachment' : 'inline';
        if (isset($filename)) {
            $disposition.= ",filename=\"{$filename}\"";
        }
        $response = $response->withHeader('Content-Disposition',$disposition);

        return $response;
    }

    #region StatusCheckers
    public static function isInfo(ResponseInterface $response) : bool
    {
        return $response->getStatusCode() >= 100 && $response->getStatusCode() < 200;
    }
    public static function isSuccess(ResponseInterface $response) : bool
    {
        return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
    }
    public static function isRedirect(ResponseInterface $response) : bool
    {
        return $response->getStatusCode() >= 300 && $response->getStatusCode() < 400;
    }
    public static function isClientError(ResponseInterface $response) : bool
    {
        return $response->getStatusCode() >= 400 && $response->getStatusCode() < 500;
    }
    public static function isServerError(ResponseInterface $response) : bool
    {
        return $response->getStatusCode() >= 500;
    }
    public static function isError(ResponseInterface $response) : bool
    {
        return $response->getStatusCode() >= 400;
    }
    #endregion

    #region AuthorizationSchemes
    private static $authenticationSchemeClasses;

    public static function addAuthenticationScheme(string $authenticationSchemeClass)
    {
        if (!class_exists($authenticationSchemeClass)) {
            throw new InvalidArgumentException(sprintf('Class %s does not exists.', $authenticationSchemeClass));
        }
        if (!is_subclass_of($authenticationSchemeClass, AbstractAuthorizationHeader::class)) {
            throw new InvalidArgumentException(sprintf(
                '%s MUST inherit from %s',
                $authenticationSchemeClass,
                AbstractAuthorizationHeader::class
            ));
        }
        $type = $authenticationSchemeClass::getAuthorizationType();
        static::$authenticationSchemeClasses[$type] = $authenticationSchemeClass;
    }

    public static function getAuthorizationHeaders(MessageInterface $message) : ?AbstractAuthorizationHeader
    {
        $headers = $message->getHeader('Authorization');

        if (empty($headers)) {
            return null;
        }

        $authorizations = [];
        foreach ($headers as $header) {
            $authorizations[] = static::createAuthHeaderFromString($header);
        }
        return $authorizations;
    }

    private static function createAuthHeaderFromString(string $header)
    {
        $wsp = strpos($header, ' ');
        $type = ucfirst(strtolower(substr($header, 0, $wsp)));
        $content = substr($header, $wsp + 1);
        if (!array_key_exists($type, static::$authenticationSchemeClasses)) {
            return new GenericAuthorizationHeader($type, $content);
        }

        $authSch = static::$authenticationSchemeClasses[$type];
        $authHeader = new $authSch();
        return $authHeader->withCredentials($content);
    }
    #endregion

    #region UploadedFile
    public function createUploadedFile(
        string $tmp_name,
        ?int $size = null,
        int $error = \UPLOAD_ERR_OK,
        ?string $name = null,
        ?string $type = null
    ) {
        $streamFactory = $this->hfm->getStreamFactory();
        $uploadFactory = $this->hfm->getUploadedFileFactory();
        $stream = $streamFactory->createStreamFromFile($tmp_name);
        return $uploadFactory->createUploadedFile($stream, $size, $error, $name, $type);
    }
    private function normalizeFilesRecursive(array $tmp_name, array $size, array $error, array $name, array $type)
    {
        $output = [];

        foreach ($tmp_name as $k => $file) {
            if (!is_array($file)) {
                $output[$k] = $this->createUploadedFile($tmp_name[$k], $size[$k], $error[$k], $name[$k], $type[$k]);
                continue;
            }
            $output[$k] = $this->normalizeFilesRecursive($tmp_name[$k], $size[$k], $error[$k], $name[$k], $type[$k]);
        }

        return $output;
    }
    public function normalizeFiles(array $files)
    {
        $output = [];
        foreach ($files as $name => $file) {
            if (!is_array($file['error'])) {
                $output[$name] = $this->createUploadedFile(
                    $file['tmp_name'],
                    $file['size'] ?? null,
                    $file['error'],
                    $file['name'] ?? null,
                    $file['type'] ?? null
                );
                continue;
            }
            $output[$name] = $this->normalizeFilesRecursive(
                $file['tmp_name'],
                $file['size'],
                $file['error'],
                $file['name'],
                $file['type']
            );
        }
        return $output;
    }
    public static function getFileClientExt(UploadedFileInterface $file)
    {
        $name = $file->getClientFilename();
        return substr($name, strrpos($name, '.') + 1);
    }

    public static function getFileMediaType(UploadedFileInterface $file)
    {
        $uri = $file->getStream()->getMetadata('uri');
        if (is_null($uri) || !is_file($uri)) {
            return $file->getClientMediaType();
        }
        return mime_content_type($uri);
    }

    public static function isFileType(UploadedFileInterface $file, array $types)
    {
        $exts = array_filter($types, function($type) {
            return strpos($type, '/') === false;
        });
        $mimes = array_filter($types, function($type) {
            return strpos($type, '/') !== false;
        });

        if (empty($exts)) {
            $exts = array_map(function($ext) {
                return ltrim($ext, '.');
            }, $exts);
            $ext = static::getFileClientExt($file);
            if (in_array($ext, $exts)) {
                return true;
            }
        }

        if (empty($mimes)) {
            $mime = static::getFileMediaType($file);
            if (in_array($mime, $mimes)) {
                return true;
            }
        }

        return false;
    }

    public static function isFileExceededSize(UploadedFileInterface $file, int $maxSize)
    {
        return $file->getSize() > $maxSize;
    }

    public static function throwFileErrorException(UploadedFileInterface $file)
    {
        switch ($file->getError()) {
            case UPLOAD_ERR_INI_SIZE:
                throw new UploadFileExceedsIniMaxSizeException();
            case UPLOAD_ERR_FORM_SIZE:
                throw new UploadFileExceedsFormMaxSizeException();
            case UPLOAD_ERR_PARTIAL:
                throw new UploadFilePartialException();
            case UPLOAD_ERR_NO_FILE:
                throw new UploadFileMissedException();
            case UPLOAD_ERR_NO_TMP_DIR:
                throw new UploadFileMissingTempFolderException();
            case UPLOAD_ERR_CANT_WRITE:
                throw new UploadFileFailedWriteException();
            case UPLOAD_ERR_EXTENSION:
                throw new UploadFileStoppedByExtensionException();
        }
    }
    #endregion

    public static function getContent(MessageInterface $message)
    {
        $type = $message->getHeader('Content-Type');

        if (empty($type)) {
            return (string)$message->getBody();
        }

        $parser = BodyParserHandler::find($type[0]);
        if (empty($parser)) {
            return (string)$message->getBody();
        }

        return $parser->parse($message->getBody(), $type[0]);
    }
}

HttpHelper::addAuthenticationScheme(BasicAuthorizationHeader::class);
HttpHelper::addAuthenticationScheme(BearerAuthorizationHeader::class);