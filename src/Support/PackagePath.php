<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Support;

use RuntimeException;

final class PackagePath
{
    public const DEMO_REGISTRY_DIR = 'bladcn-components';

    public const DEFAULT_GITHUB_REGISTRY = 'AiluraCode/bladcn-components';

    public static function root(): string
    {
        return dirname(__DIR__, 2);
    }

    public static function demoRegistryNear(string $basePath): ?string
    {
        $basePath = mb_rtrim(str_replace('\\', '/', $basePath), '/');

        $candidates = [
            dirname(self::root()).'/'.self::DEMO_REGISTRY_DIR,
            $basePath.'/../'.self::DEMO_REGISTRY_DIR,
            $basePath.'/'.self::DEMO_REGISTRY_DIR,
        ];

        foreach ($candidates as $candidate) {
            $resolved = realpath($candidate);

            if ($resolved === false) {
                continue;
            }

            if (is_dir($resolved.'/resources/views/components/ui')) {
                return $resolved;
            }
        }

        return null;
    }

    public static function registry(): string
    {
        $path = self::demoRegistryNear(self::root());

        if ($path !== null) {
            return $path;
        }

        throw new RuntimeException(
            'bladcn-components registry not found. Clone it next to bladcn-cli or set BLADCN_REGISTRY.',
        );
    }

    public static function initStubsRoot(string $projectRoot): string
    {
        $registry = self::demoRegistryNear($projectRoot) ?? self::demoRegistryNear(self::root());

        if ($registry !== null && is_dir($registry.'/resources')) {
            return $registry;
        }

        throw new RuntimeException(
            'Init stubs not found in bladcn-components. Set BLADCN_REGISTRY to the demo registry path.',
        );
    }
}
