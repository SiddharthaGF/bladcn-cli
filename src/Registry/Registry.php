<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Registry;

use AiluraCode\Bladcn\Config\BladcnConfig;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use ZipArchive;

final readonly class Registry
{
    private const COMPONENTS_SUBPATHS = [
        'resources/views/components/ui',
        'components',
    ];

    private string $root;

    private string $componentsRoot;

    public function __construct(
        private BladcnConfig $config,
    ) {
        $this->root = $this->resolveRoot();
        $this->componentsRoot = $this->resolveComponentsRoot();
    }

    public function root(): string
    {
        return $this->root;
    }

    public function componentsRoot(): string
    {
        return $this->componentsRoot;
    }

    /** @return list<string> */
    public function listComponents(): array
    {
        $components = [];

        foreach ($this->scandirEntries($this->componentsRoot) as $entry) {
            if ($entry === '.') {
                continue;
            }

            if ($entry === '..') {
                continue;
            }

            $path = $this->componentsRoot.DIRECTORY_SEPARATOR.$entry;

            if (is_dir($path)) {
                $components[] = $entry;

                continue;
            }

            if (str_ends_with($entry, '.blade.php')) {
                $components[] = mb_substr($entry, 0, -mb_strlen('.blade.php'));
            }
        }

        sort($components);

        return $components;
    }

    public function hasComponent(string $name): bool
    {
        return in_array($name, $this->listComponents(), true);
    }

    /** @return list<string> */
    public function dependencies(string $name): array
    {
        return $this->manifest($name)->dependencies;
    }

    public function manifest(string $name): ComponentManifest
    {
        $path = $this->manifestPath($name);

        if ($path === null) {
            return new ComponentManifest;
        }

        return ComponentManifest::fromFile($path);
    }

    public function componentSourcePath(string $name): string
    {
        $folder = $this->componentsRoot.DIRECTORY_SEPARATOR.$name;

        if (is_dir($folder)) {
            return $folder;
        }

        $file = $this->componentsRoot.DIRECTORY_SEPARATOR.$name.'.blade.php';

        if (is_file($file)) {
            return $file;
        }

        throw new RuntimeException('Component not found in registry: '.$name);
    }

    public function isDirectoryComponent(string $name): bool
    {
        return is_dir($this->componentsRoot.DIRECTORY_SEPARATOR.$name);
    }

    private function manifestPath(string $name): ?string
    {
        $folder = $this->componentsRoot.DIRECTORY_SEPARATOR.$name;

        if (! is_dir($folder)) {
            return null;
        }

        $path = $folder.DIRECTORY_SEPARATOR.'dependencies.json';

        return is_file($path) ? $path : null;
    }

    private function resolveComponentsRoot(): string
    {
        foreach (self::COMPONENTS_SUBPATHS as $subpath) {
            $nested = $this->root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $subpath);

            if (is_dir($nested)) {
                return $this->resolveExistingPath($nested);
            }
        }

        return $this->root;
    }

    private function resolveRoot(): string
    {
        if ($this->config->registryPath !== null) {
            $path = $this->config->registryPath;

            throw_unless(is_dir($path), RuntimeException::class, 'Local registry not found: '.$path);

            return $this->resolveExistingPath($path);
        }

        throw_if($this->config->registryRepo === null, RuntimeException::class, 'Set `registry` or `registryPath` in bladcn.json.');

        return $this->downloadGithubRegistry(
            $this->config->registryRepo,
            $this->config->registryBranch,
        );
    }

    private function downloadGithubRegistry(string $repo, string $branch): string
    {
        $cacheDir = $this->cacheDirectory($repo, $branch);

        if (is_dir($cacheDir)) {
            return $cacheDir;
        }

        if (! is_dir(dirname($cacheDir))) {
            mkdir(dirname($cacheDir), 0775, true);
        }

        $zipUrl = sprintf('https://github.com/%s/archive/refs/heads/%s.zip', $repo, $branch);
        $zipPath = $cacheDir.'.zip';

        $context = stream_context_create([
            'http' => [
                'header' => "User-Agent: bladcn-cli\r\n",
                'timeout' => 60,
            ],
        ]);

        $bytes = @file_get_contents($zipUrl, false, $context);

        throw_if(
            $bytes === false,
            RuntimeException::class,
            'Could not download registry from GitHub: '.$zipUrl.'. '
            .'Set BLADCN_REGISTRY in your Laravel .env to a local path (e.g. ../bladcn-components) '
            .'or install the package via Composer to use the bundled registry.',
        );

        file_put_contents($zipPath, $bytes);

        $zip = new ZipArchive;

        throw_if($zip->open($zipPath) !== true, RuntimeException::class, 'Could not open registry ZIP archive.');

        $extractTo = $cacheDir.'_tmp';
        if (is_dir($extractTo)) {
            $this->removeDirectory($extractTo);
        }

        mkdir($extractTo, 0775, true);
        $zip->extractTo($extractTo);
        $zip->close();
        unlink($zipPath);

        $entries = array_values(array_filter(
            $this->scandirEntries($extractTo),
            fn (string $entry): bool => $entry !== '.' && $entry !== '..',
        ));

        throw_if(count($entries) !== 1, RuntimeException::class, 'Unexpected structure in registry ZIP.');

        rename($extractTo.DIRECTORY_SEPARATOR.$entries[0], $cacheDir);
        $this->removeDirectory($extractTo);

        return $cacheDir;
    }

    private function cacheDirectory(string $repo, string $branch): string
    {
        $home = getenv('HOME');
        $base = ($home !== false && $home !== '') ? $home : sys_get_temp_dir();

        return $base
            .DIRECTORY_SEPARATOR.'.bladcn'
            .DIRECTORY_SEPARATOR.'cache'
            .DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $repo)
            .DIRECTORY_SEPARATOR.$branch;
    }

    /**
     * @return list<string>
     */
    private function scandirEntries(string $directory): array
    {
        $entries = scandir($directory);

        return $entries !== false ? $entries : [];
    }

    private function resolveExistingPath(string $path): string
    {
        $resolved = realpath($path);

        return $resolved !== false ? $resolved : $path;
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($directory);
    }
}
