<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Installer;

use AiluraCode\Bladcn\Config\BladcnConfig;
use AiluraCode\Bladcn\Registry\DependencyResolver;
use AiluraCode\Bladcn\Registry\Registry;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class ComponentInstaller
{
    private readonly DependencyResolver $dependencyResolver;

    private readonly ExternalDependencyManager $externalDependencyManager;

    public function __construct(
        private BladcnConfig $config,
        private readonly Registry $registry,
        ?DependencyResolver $dependencyResolver = null,
        ?ExternalDependencyManager $externalDependencyManager = null,
    ) {
        $this->dependencyResolver = $dependencyResolver ?? new DependencyResolver($registry);
        $this->externalDependencyManager = $externalDependencyManager ?? new ExternalDependencyManager;
    }

    /**
     * @return list<string> ordered install plan (dependencies first)
     */
    public function resolveInstallPlan(string $component, bool $withDependencies = true): array
    {
        throw_unless($this->registry->hasComponent($component), RuntimeException::class, 'Unknown component: '.$component);

        $plan = [];
        $this->collect($component, $plan, $withDependencies);

        return $plan;
    }

    /**
     * @return array{components: list<string>, composer: list<string>}
     */
    public function install(string $component, bool $overwrite = false, bool $withDependencies = true, bool $installExternal = true, bool $dryRun = false): array
    {
        $plan = $this->resolveInstallPlan($component, $withDependencies);
        $targetRoot = $this->config->componentsAbsolutePath();

        if (! is_dir($targetRoot)) {
            mkdir($targetRoot, 0775, true);
        }

        $installed = [];

        foreach ($plan as $name) {
            if ($this->isInstalled($name) && ! $overwrite) {
                continue;
            }

            if (! $dryRun) {
                $this->copyComponent($name, $targetRoot, $overwrite);
            }

            $installed[] = $name;
        }

        $composerInstalled = [];

        if ($installExternal && $installed !== []) {
            $composerPackages = $this->dependencyResolver->collectComposerDependencies($installed);
            $composerInstalled = $this->externalDependencyManager->installComposerPackages(
                $this->config->projectRoot(),
                $composerPackages,
                $dryRun,
            );
        }

        if (! $dryRun && $installed !== []) {
            $resolved = array_values(array_unique([...$this->config->resolved, ...$installed]));
            $this->config = $this->config->withResolved($resolved);
            $this->config->save();
        }

        return [
            'components' => $installed,
            'composer' => $composerInstalled,
        ];
    }

    public function isInstalled(string $name): bool
    {
        $targetRoot = $this->config->componentsAbsolutePath();
        $folder = $targetRoot.DIRECTORY_SEPARATOR.$name;
        $file = $targetRoot.DIRECTORY_SEPARATOR.$name.'.blade.php';

        return is_dir($folder) || is_file($file);
    }

    /**
     * @param  list<string>  $plan
     */
    private function collect(string $name, array &$plan, bool $withDependencies): void
    {
        if (in_array($name, $plan, true)) {
            return;
        }

        if ($withDependencies) {
            foreach ($this->registry->dependencies($name) as $dependency) {
                throw_unless($this->registry->hasComponent($dependency), RuntimeException::class, sprintf('Dependency `%s` of `%s` does not exist in the registry.', $dependency, $name));

                $this->collect($dependency, $plan, true);
            }
        }

        $plan[] = $name;
    }

    private function copyComponent(string $name, string $targetRoot, bool $overwrite): void
    {
        $source = $this->registry->componentSourcePath($name);

        if (is_dir($source)) {
            $destination = $targetRoot.DIRECTORY_SEPARATOR.$name;

            if (is_dir($destination) && ! $overwrite) {
                return;
            }

            if (is_dir($destination)) {
                $this->removeDirectory($destination);
            }

            $this->copyDirectory($source, $destination);

            return;
        }

        throw_unless(is_file($source), RuntimeException::class, 'Invalid source for component: '.$name);

        $destination = $targetRoot.DIRECTORY_SEPARATOR.$name.'.blade.php';

        if (is_file($destination) && ! $overwrite) {
            return;
        }

        throw_unless(copy($source, $destination), RuntimeException::class, sprintf('Could not copy %s.blade.php', $name));
    }

    private function copyDirectory(string $source, string $destination): void
    {
        if (! is_dir($destination)) {
            mkdir($destination, 0775, true);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            $relative = mb_substr($item->getPathname(), mb_strlen($source) + 1);

            if ($relative === 'dependencies.json') {
                continue;
            }

            $target = $destination.DIRECTORY_SEPARATOR.$relative;

            if ($item->isDir()) {
                if (! is_dir($target)) {
                    mkdir($target, 0775, true);
                }

                continue;
            }

            $parent = dirname($target);
            if (! is_dir($parent)) {
                mkdir($parent, 0775, true);
            }

            throw_unless(copy($item->getPathname(), $target), RuntimeException::class, 'Could not copy '.$relative);
        }
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
