<?php

namespace Hyqo\Pair;

function parse_pair(string $string, bool $throwOnError = false): ?array
{
    $error = static function () use ($throwOnError, $string) {
        if ($throwOnError) {
            throw new \InvalidArgumentException(sprintf('Invalid string: %s', $string));
        }

        return null;
    };

    if (!preg_match('/^ *(?P<key>[\w-]+) ?= *(?P<value>.*) *$/', trim($string), $matches)) {
        return $error();
    }

    $key = $matches['key'];

    if (!isset($matches['value'])) {
        return [$key, ''];
    }

    $value = $matches['value'];

    if (null !== $unquotedValue = unquote($value)) {
        return [$key, $unquotedValue];
    }

    if (preg_match('/^(?P<value>[^\s"\']*)$/', $value, $matches)) {
        return [$key, $matches['value']];
    }

    return $error();
}

function build_pair(?string $key, ?string $value): string
{
    if (!preg_match('/^\w+$/', $key)) {
        throw new \InvalidArgumentException('Key must contains only a-zA-Z0-9_');
    }

    if (preg_match('/[\r\n\t\f\v]/', $value)) {
        $whitespaces = [
            "/\r/" => '\r',
            "/\n/" => '\n',
            "/\t/" => '\t',
            "/\f/" => '\f',
            "/\v/" => '\v',
        ];

        $value = preg_replace(array_keys($whitespaces), array_values($whitespaces), $value);
    }

    if (preg_match('/\W+/', $value)) {
        $value = sprintf('"%s"', addcslashes($value, '"'));
    }

    return sprintf("%s=%s", $key, $value);
}

function unquote(string $string): ?string
{
    if (preg_match(
        '/^(?:"(?P<double_quoted>(?:(?:\\\\\\\\)+\\\\"|(?<!\\\\)\\\\"|\\\\[^"]|[^"\\\\])*)"|\'(?P<quoted>(?:(?:\\\\\\\\)+\\\\\'|(?<!\\\\)\\\\\'|\\\\[^\']|[^\'\\\\])*)\')$/',
        $string,
        $matches
    )) {
        return stripcslashes($matches['quoted'] ?? $matches['double_quoted']);
    }

    return null;
}
