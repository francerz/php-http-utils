<?php

namespace Francerz\Http\Tools;

use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

interface BodyParserInterface
{
    public function getSupportedTypes() : array;
    public function parse(StreamInterface $content, string $contentType = '');
    public function unparse(StreamFactoryInterface $streamFactory, $content, string $contentType = '') : StreamInterface;
}