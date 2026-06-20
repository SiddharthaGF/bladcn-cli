<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Installer;

use AiluraCode\Bladcn\Config\BladcnConfig;
use AiluraCode\Bladcn\Registry\DependencyResolver;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class ComponentRemover
{
    public function __construct(
        private BladcnConfig $config,
        private readonly DependencyResolver $dependencyResolver,
    ) {}

    /**
     * @param  list<string>  $components
     * @return array{removed: list<string>, orphans: list<string>, composerOrphans: list<string>}
     */
    public function planRemoval(array $components, bool $withOrphans = true): array
    {
        $components = array_values(array_unique($components));
        $resolved = $this->config->resolved;

        foreach ($components as $component) {
            throw_if(! in_array($component, $resolved, true) && ! $this->isInstalledOnDisk($component), RuntimeException::class, 'Component not installed: '.$component);
        }

        $stillInstalled = array_values(array_diff($resolved, $components));
        $orphans = $withOrphans
            ? $this->dependencyResolver->findOrphanInternalDependencies($stillInstalled)
            : [];

        $removedAll = array_values(array_unique([...$components, ...$orphans]));
        $stillAfterOrphans = array_values(array_diff($stillInstalled, $orphans));
        $composerOrphans = $this->dependencyResolver->findOrphanComposerPackages($removedAll, $stillAfterOrphans);

        return [
            'removed' => $removedAll,
            'orphans' => $orphans,
            'composerOrphans' => $composerOrphans,
        ];
    }

    /**
     * @param  list<string>  $names
     * @return array{removed: list<string>, composerOrphans: list<string>}
     */
    public function removeNames(array $names): array
    {
        $names = array_values(array_unique($names));
        $removed = [];

        foreach ($names as $name) {
            if ($this->removeFromDisk($name)) {
                $removed[] = $name;
            }
        }

        if ($removed !== []) {
            $resolved = array_values(array_diff($this->config->resolved, $removed));
            $this->config = $this->config->withResolved($resolved);
            $this->config->save();
        }

        $stillInstalled = $this->config->resolved;

        return [
            'removed' => $removed,
            'composerOrphans' => $this->dependencyResolver->findOrphanComposerPackages($removed, $stillInstalled),
        ];
    }

    /**
     * @param  list<string>  $components
     * @return array{removed: list<string>, composerOrphans: list<string>}
     */
    public function remove(array $components, bool $withOrphans = true): array
    {
        $plan = $this->planRemoval($components, $withOrphans);

        return $this->removeNames($plan['removed']);
    }

    public function isInstalled(string $name): bool
    {
        return in_array($name, $this->config->resolved, true) || $this->isInstalledOnDisk($name);
    }

    private function isInstalledOnDisk(string $name): bool
    {
        $targetRoot = $this->config->componentsAbsolutePath();
        $folder = $targetRoot.DIRECTORY_SEPARATOR.$name;
        $file = $targetRoot.DIRECTORY_SEPARATOR.$name.'.blade.php';

        return is_dir($folder) || is_file($file);
    }

    private function removeFromDisk(string $name): bool
    {
        $targetRoot = $this->config->componentsAbsolutePath();
        $folder = $targetRoot.DIRECTORY_SEPARATOR.$name;
        $file = $targetRoot.DIRECTORY_SEPARATOR.$name.'.blade.php';

        if (is_dir($folder)) {
            $this->removeDirectory($folder);

            return true;
        }

        if (is_file($file)) {
            unlink($file);

            return true;
        }

        return false;
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
