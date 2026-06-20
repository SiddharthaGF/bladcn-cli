<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Tests;

use Override;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

abstract class BladcnTestCase extends TestCase
{
    private string $tempDir;

    private ?string $previousWorkingDirectory = null;

    #[Override]
    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/bladcn-test-'.uniqid('', true);
        mkdir($this->tempDir, 0775, true);
    }

    #[Override]
    protected function tearDown(): void
    {
        if ($this->previousWorkingDirectory !== null) {
            chdir($this->previousWorkingDirectory);
            $this->previousWorkingDirectory = null;
        }

        $this->removeDirectory($this->tempDir);
    }

    protected function tempDir(): string
    {
        return $this->tempDir;
    }

    protected function chdirToTemp(): void
    {
        $cwd = getcwd();
        $this->previousWorkingDirectory = $cwd !== false ? $cwd : '.';
        chdir($this->tempDir);
    }

    protected function fixturePath(string $path = ''): string
    {
        $base = __DIR__.'/fixtures';

        return $path === '' ? $base : $base.'/'.$path;
    }

    protected function registryComponentsPath(): string
    {
        return $this->registryFixturePath().'/resources/views/components/ui';
    }

    protected function registryFixturePath(): string
    {
        return $this->fixturePath('registry');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function writeBladcnJson(array $data = []): void
    {
        $defaults = [
            'componentsPath' => 'resources/views/components/ui',
            'registry' => $this->registryFixturePath(),
            'registryBranch' => 'main',
            'resolved' => [],
        ];

        $json = json_encode(array_merge($defaults, $data), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        throw_if($json === false, RuntimeException::class, 'Could not serialize test bladcn.json.');

        file_put_contents($this->tempDir.'/bladcn.json', $json.PHP_EOL);
    }

    /**
     * @param  array<string, string>  $require
     */
    protected function writeComposerJson(array $require = []): void
    {
        $json = json_encode(['require' => $require], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        throw_if($json === false, RuntimeException::class, 'Could not serialize test composer.json.');

        file_put_contents($this->tempDir().'/composer.json', $json.PHP_EOL);
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
