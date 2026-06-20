<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Installer;

use RuntimeException;

final class ExternalDependencyManager
{
    /**
     * @param  list<string>  $packages
     * @return list<string> installed packages
     */
    public function installComposerPackages(string $projectRoot, array $packages, bool $dryRun = false): array
    {
        $packages = array_values(array_unique($packages));

        if ($packages === []) {
            return [];
        }

        $missing = $this->missingComposerPackages($projectRoot, $packages);

        if ($missing === []) {
            return [];
        }

        if ($dryRun) {
            return $missing;
        }

        $composer = $this->composerBinary();

        if ($composer === null) {
            throw new RuntimeException(
                'Composer not found. Install manually: composer require '.implode(' ', $missing)
            );
        }

        $command = escapeshellarg($composer).' require '.implode(' ', array_map(escapeshellarg(...), $missing));
        $output = [];
        $exitCode = 0;

        exec($command.' 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException(
                'composer require failed: '.implode(PHP_EOL, $output)
            );
        }

        return $missing;
    }

    /**
     * @param  list<string>  $packages
     * @return list<string>
     */
    public function missingComposerPackages(string $projectRoot, array $packages): array
    {
        $composerJsonPath = $projectRoot.'/composer.json';

        if (! is_file($composerJsonPath)) {
            return $packages;
        }

        $data = json_decode((string) file_get_contents($composerJsonPath), true);

        if (! is_array($data)) {
            return $packages;
        }

        $installed = [];

        foreach (['require', 'require-dev'] as $section) {
            if (isset($data[$section]) && is_array($data[$section])) {
                foreach (array_keys($data[$section]) as $name) {
                    $installed[] = (string) $name;
                }
            }
        }

        return array_values(array_filter(
            $packages,
            fn (string $package): bool => ! in_array($package, $installed, true),
        ));
    }

    private function composerBinary(): ?string
    {
        $paths = ['composer', '/usr/local/bin/composer', '/usr/bin/composer'];

        foreach ($paths as $path) {
            $output = [];
            $exitCode = 0;
            exec('command -v '.escapeshellarg($path).' 2>/dev/null', $output, $exitCode);

            if ($exitCode === 0 && $output !== []) {
                return mb_trim($output[0]);
            }
        }

        return null;
    }
}
