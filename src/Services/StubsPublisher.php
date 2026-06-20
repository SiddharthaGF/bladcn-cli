<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Services;

use AiluraCode\Bladcn\Support\InitOptions;
use AiluraCode\Bladcn\Support\PackagePath;
use RuntimeException;

final readonly class StubsPublisher
{
    /**
     * @return list<string> relative paths written or updated
     */
    public function publish(string $projectRoot, InitOptions $options): array
    {
        $stubsRoot = PackagePath::initStubsRoot($projectRoot);
        $written = [];

        $written = [...$written, ...$this->publishStub(
            $stubsRoot,
            $projectRoot,
            'app/Providers/BladcnServiceProvider.php',
            $options->force,
        )];

        $written = [...$written, ...$this->publishStub(
            $stubsRoot,
            $projectRoot,
            'resources/views/partials/bladcn-boot.blade.php',
            $options->force,
        )];

        $written = [...$written, ...$this->publishStub(
            $stubsRoot,
            $projectRoot,
            'resources/js/bladcn.js',
            $options->force,
        )];

        $written = [...$written, ...$this->publishStub(
            $stubsRoot,
            $projectRoot,
            'resources/css/bladcn-base.css',
            $options->force,
        )];

        $themeRelative = 'resources/css/'.$options->themeFile;
        $written = [...$written, ...$this->publishThemedCss($stubsRoot, $projectRoot, $options, $themeRelative)];

        $cssRelative = 'resources/css/'.$options->cssFile;
        $cssPath = $projectRoot.'/'.$cssRelative;

        if (! is_file($cssPath) && $options->cssFile === 'app.css') {
            $written = [...$written, ...$this->publishAppCss($stubsRoot, $projectRoot, $options)];
        } elseif (is_file($cssPath) && $this->appendBladcnImports($projectRoot, $options)) {
            $written[] = $cssRelative;
        }

        if ($this->appendBladcnJsImport($projectRoot)) {
            $written[] = 'resources/js/app.js';
        }

        return array_values(array_unique($written));
    }

    /**
     * @return list<string>
     */
    private function publishAppCss(string $stubsRoot, string $projectRoot, InitOptions $options): array
    {
        $relative = 'resources/css/'.$options->cssFile;
        $destination = $projectRoot.'/'.$relative;
        $source = $stubsRoot.'/resources/css/app.css';

        throw_unless(is_file($source), RuntimeException::class, 'App CSS stub not found in registry.');

        $content = (string) file_get_contents($source);
        $content = str_replace('bladcn-theme.css', $options->themeFile, $content);

        $parent = dirname($destination);
        if (! is_dir($parent)) {
            mkdir($parent, 0775, true);
        }

        file_put_contents($destination, $content);

        return [$relative];
    }

    /**
     * @return list<string>
     */
    private function publishStub(string $stubsRoot, string $projectRoot, string $relativePath, bool $force): array
    {
        $source = $stubsRoot.'/'.$relativePath;
        $destination = $projectRoot.'/'.$relativePath;

        throw_unless(is_file($source), RuntimeException::class, 'Stub not found in registry: '.$relativePath);

        if (is_file($destination) && ! $force) {
            return [];
        }

        $parent = dirname($destination);
        if (! is_dir($parent)) {
            mkdir($parent, 0775, true);
        }

        throw_unless(copy($source, $destination), RuntimeException::class, 'Could not copy stub: '.$relativePath);

        return [$relativePath];
    }

    /**
     * @return list<string>
     */
    private function publishThemedCss(string $stubsRoot, string $projectRoot, InitOptions $options, string $themeRelative): array
    {
        $destination = $projectRoot.'/'.$themeRelative;

        if (is_file($destination) && ! $options->force) {
            return [];
        }

        $source = $options->withDarkMode
            ? $stubsRoot.'/resources/css/bladcn-theme.dark.css'
            : $stubsRoot.'/resources/css/bladcn-theme.css';

        throw_unless(is_file($source), RuntimeException::class, 'CSS theme stub not found in registry.');

        $parent = dirname($destination);
        if (! is_dir($parent)) {
            mkdir($parent, 0775, true);
        }

        throw_unless(copy($source, $destination), RuntimeException::class, 'Could not copy CSS theme.');

        return [$themeRelative];
    }

    private function appendBladcnImports(string $projectRoot, InitOptions $options): bool
    {
        $cssPath = $projectRoot.'/resources/css/'.$options->cssFile;

        if (! is_file($cssPath)) {
            return false;
        }

        $imports = [
            './bladcn-base.css',
            './'.$options->themeFile,
        ];

        $content = (string) file_get_contents($cssPath);
        $missing = [];

        foreach ($imports as $import) {
            $file = basename(str_replace('./', '', $import));

            if (! str_contains($content, $file)) {
                $missing[] = '@import "'.$import.'";';
            }
        }

        if ($missing === []) {
            return false;
        }

        $updated = mb_rtrim($content).PHP_EOL.PHP_EOL.implode(PHP_EOL, $missing).PHP_EOL;

        file_put_contents($cssPath, $updated);

        return true;
    }

    private function appendBladcnJsImport(string $projectRoot): bool
    {
        $jsPath = $projectRoot.'/resources/js/app.js';

        if (! is_file($jsPath)) {
            return false;
        }

        $content = (string) file_get_contents($jsPath);

        if (str_contains($content, './bladcn')) {
            return false;
        }

        $import = "import './bladcn';";
        $updated = mb_rtrim($content).PHP_EOL.PHP_EOL.$import.PHP_EOL;

        file_put_contents($jsPath, $updated);

        return true;
    }
}
