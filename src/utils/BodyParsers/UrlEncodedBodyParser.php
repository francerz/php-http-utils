<?php

namespace Francerz\Http\Utils\BodyParsers;

use Francerz\Http\Utils\BodyParserInterface;
use Francerz\Http\Utils\Constants\MediaTypes;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class UrlEncodedBodyParser implements BodyParserInterface
{
    public function getSupportedTypes(): array
    {
        return array(
            MediaTypes::APPLICATION_X_WWW_FORM_URLENCODED
        );
    }

    public function parse(StreamInterface $content, string $contentType = '')
    {
        parse_str((string) $content, $result);
        return $result;
    }
    public function unparse(StreamFactoryInterface $streamFactory, $content, string $contentType = ''): StreamInterface
    {
        return $streamFactory->createStream(http_build_query($content));
    }
}
