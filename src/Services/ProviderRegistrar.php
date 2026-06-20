<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Services;

final class ProviderRegistrar
{
    private const PROVIDER_CLASS = 'App\\Providers\\BladcnServiceProvider';

    public function register(string $projectRoot): bool
    {
        $providersFile = $projectRoot.'/bootstrap/providers.php';

        if (! is_file($providersFile)) {
            return false;
        }

        $content = (string) file_get_contents($providersFile);

        if (str_contains($content, self::PROVIDER_CLASS)) {
            return false;
        }

        $needle = '];';
        $position = mb_strrpos($content, $needle);

        if ($position === false) {
            return false;
        }

        $insertion = "    App\\Providers\\BladcnServiceProvider::class,\n";
        $updated = mb_substr($content, 0, $position).$insertion.mb_substr($content, $position);

        file_put_contents($providersFile, $updated);

        return true;
    }

    public function providerClass(): string
    {
        return self::PROVIDER_CLASS;
    }
}
