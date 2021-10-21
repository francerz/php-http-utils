<?php

namespace Francerz\Http\Utils;

use Francerz\Http\Utils\BodyParsers\JsonBodyParser;
use Francerz\Http\Utils\BodyParsers\UrlEncodedBodyParser;
use InvalidArgumentException;

class BodyParserHandler
{
    private static $parsers = array();
    private static $typesIndex = array();

    public static function register(string $bodyParserClass)
    {
        if (!is_subclass_of($bodyParserClass, BodyParserInterface::class)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Parser class MUST implement %s.',
                    BodyParserInterface::class
                )
            );
        }

        $filter = array_filter(static::$parsers, function ($v) use ($bodyParserClass) {
            return $v instanceof $bodyParserClass;
        });

        if (count($filter) > 0) return;

        $parser = new $bodyParserClass();
        static::$parsers[] = $parser;
        foreach ($parser->getSupportedTypes() as $type) {
            $type = strtolower($type);
            static::$typesIndex[$type] = $parser;
        }
    }

    public static function find(string $type): ?BodyParserInterface
    {
        $lim = strpos($type, ';');
        $type = strtolower($lim === false ? $type : substr($type, 0, $lim));

        if (array_key_exists($type, static::$typesIndex)) {
            return static::$typesIndex[$type];
        }
        return null;
    }
}

// Registers default BodyParsers
BodyParserHandler::register(UrlEncodedBodyParser::class);
BodyParserHandler::register(JsonBodyParser::class);
