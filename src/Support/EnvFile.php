<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Support;

final class EnvFile
{
    public static function load(string $path): void
    {
        if (! is_file($path)) {
            return;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return;
        }

        foreach (explode("\n", $contents) as $line) {
            self::loadLine($line);
        }
    }

    private static function loadLine(string $line): void
    {
        $line = mb_trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            return;
        }

        if (str_starts_with($line, 'export ')) {
            $line = mb_trim(mb_substr($line, 7));
        }

        $separator = mb_strpos($line, '=');

        if ($separator === false) {
            return;
        }

        $name = mb_trim(mb_substr($line, 0, $separator));

        if ($name === '') {
            return;
        }

        if (getenv($name) !== false) {
            return;
        }

        $value = self::parseValue(mb_substr($line, $separator + 1));

        putenv($name.'='.$value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    private static function parseValue(string $value): string
    {
        $value = mb_trim($value);

        if ($value === '') {
            return '';
        }

        $quote = $value[0];

        if ($quote !== '"' && $quote !== "'") {
            $comment = mb_strpos($value, ' #');

            if ($comment !== false) {
                return mb_trim(mb_substr($value, 0, $comment));
            }

            return $value;
        }

        if (mb_strlen($value) >= 2 && $value[mb_strlen($value) - 1] === $quote) {
            return mb_substr($value, 1, -1);
        }

        return mb_substr($value, 1);
    }
}
