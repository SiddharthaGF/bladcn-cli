<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Support;

final class ProjectPaths
{
    public static function cwdOrDot(): string
    {
        $cwd = getcwd();

        return $cwd !== false ? $cwd : '.';
    }
}
