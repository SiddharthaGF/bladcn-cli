<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Support;

final class Arr
{
    /**
     * @return list<string>
     */
    public static function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (! is_scalar($item)) {
                continue;
            }

            $items[] = (string) $item;
        }

        return array_values(array_unique($items));
    }
}
