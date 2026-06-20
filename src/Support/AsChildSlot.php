<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Support;

use Illuminate\View\ComponentAttributeBag;
use RuntimeException;

final class AsChildSlot
{
    private const FORBIDDEN_ROOT_TAGS = ['script', 'style', 'template'];

    public static function render(
        string $slotHtml,
        ComponentAttributeBag $attributes,
        string $defaultTag = 'div',
    ): string {
        $defaultTag = mb_strtolower(mb_trim($defaultTag));

        if (preg_match('/^(\s*(?:<!--[\s\S]*?-->\s*)*)<([a-zA-Z][\w:.-]*)/', $slotHtml, $root) !== 1) {
            throw_if(in_array($defaultTag, self::FORBIDDEN_ROOT_TAGS, true), RuntimeException::class, sprintf('Cannot use as-child with <%s> elements.', $defaultTag));
            $slotHtml = sprintf('<%s>%s</%s>', $defaultTag, $slotHtml, $defaultTag);
            throw_if(preg_match('/^(\s*(?:<!--[\s\S]*?-->\s*)*)<([a-zA-Z][\w:.-]*)/', $slotHtml, $root) !== 1, RuntimeException::class, 'as-child requires a single root HTML element.');
        }

        $rootTag = mb_strtolower($root[2]);

        throw_if(in_array($rootTag, self::FORBIDDEN_ROOT_TAGS, true), RuntimeException::class, sprintf('Cannot use as-child with <%s> elements.', $rootTag));

        $existingClass = self::extractRootClass($slotHtml);
        $slotHtml = self::stripRootClassAttribute($slotHtml);

        $attrs = $attributes->getAttributes();
        $classValue = $attrs['class'] ?? '';
        $incomingClass = is_string($classValue) ? mb_trim($classValue) : '';
        unset($attrs['class']);

        $mergedClass = mb_trim($existingClass.' '.$incomingClass);

        if ($mergedClass !== '') {
            $attrs['class'] = $mergedClass;
        }

        $attributesString = mb_trim((string) new ComponentAttributeBag($attrs));

        $result = preg_replace(
            '/^(\s*(?:<!--[\s\S]*?-->\s*)*)(<)([a-zA-Z][\w:.-]*)/',
            '$1$2$3 '.$attributesString,
            $slotHtml,
            1,
        );

        throw_if($result === null, RuntimeException::class, 'Failed to apply as-child attributes to slot HTML.');

        return $result;
    }

    private static function extractRootClass(string $slotHtml): string
    {
        if (preg_match('/\bclass=(["\'])(.*?)\1/s', $slotHtml, $match) !== 1) {
            return '';
        }

        return mb_trim($match[2]);
    }

    private static function stripRootClassAttribute(string $slotHtml): string
    {
        $result = preg_replace('/\s*class=(["\']).*?\1/s', '', $slotHtml, 1);

        return $result ?? $slotHtml;
    }
}
