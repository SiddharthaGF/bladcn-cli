<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Support;

final class AsChild
{
    public static function attribute(mixed $value = null): string
    {
        if ($value === null) {
            $value = 'as-child-'.resolve(AsChildCounter::class)->next();
        }

        if (! is_scalar($value)) {
            $value = '';
        }

        return 'data-bladcn="'.e((string) $value).'"';
    }
}
