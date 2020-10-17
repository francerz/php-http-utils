<?php

namespace Francerz\Http\Tools\BodyParsers;

use Francerz\Http\Tools\BodyParserInterface;
use Francerz\Http\Tools\MediaTypes;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class JsonBodyParser implements BodyParserInterface
{
    public function getSupportedTypes(): array
    {
        return array(
            MediaTypes::APPLICATION_JSON
        );
    }

    public function parse(StreamInterface $content, string $contentType = '')
    {
        return json_decode((string) $content);
    }

    public function unparse(StreamFactoryInterface $streamFactory, $content, string $contentType = ''): StreamInterface
    {
        return $streamFactory->createStream(json_encode($content));
    }
}