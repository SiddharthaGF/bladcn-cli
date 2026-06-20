<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Registry;

final readonly class DependencyResolver
{
    public function __construct(
        private Registry $registry,
    ) {}

    /** @return list<string> */
    public function transitiveInternalDependencies(string $name): array
    {
        $deps = [];
        $this->collectInternal($name, $deps);

        return $deps;
    }

    /**
     * Internal components listed as dependencies of other registry components
     * that are no longer required by any remaining installed component.
     *
     * @param  list<string>  $stillInstalled
     * @return list<string>
     */
    public function findOrphanInternalDependencies(array $stillInstalled): array
    {
        $orphans = [];

        foreach ($stillInstalled as $name) {
            if (! $this->isListedAsDependencyInRegistry($name)) {
                continue;
            }

            $needed = false;

            foreach ($stillInstalled as $other) {
                if ($other === $name) {
                    continue;
                }

                if (in_array($name, $this->transitiveInternalDependencies($other), true)) {
                    $needed = true;
                    break;
                }
            }

            if (! $needed) {
                $orphans[] = $name;
            }
        }

        return $orphans;
    }

    /**
     * @param  list<string>  $componentNames
     * @return list<string>
     */
    public function collectComposerDependencies(array $componentNames): array
    {
        $packages = [];

        foreach ($componentNames as $name) {
            foreach ($this->registry->manifest($name)->composer as $package) {
                $packages[] = $package;
            }
        }

        sort($packages);

        /** @var list<string> $unique */
        $unique = array_values(array_unique($packages));

        return $unique;
    }

    /**
     * @param  list<string>  $removedComponents
     * @param  list<string>  $stillInstalled
     * @return list<string>
     */
    public function findOrphanComposerPackages(array $removedComponents, array $stillInstalled): array
    {
        $removedPackages = $this->collectComposerDependencies($removedComponents);
        $stillRequired = $this->collectComposerDependencies($stillInstalled);

        return array_values(array_diff($removedPackages, $stillRequired));
    }

    /** @param list<string> $deps */
    private function collectInternal(string $name, array &$deps): void
    {
        foreach ($this->registry->dependencies($name) as $dependency) {
            if (in_array($dependency, $deps, true)) {
                continue;
            }

            $this->collectInternal($dependency, $deps);
            $deps[] = $dependency;
        }
    }

    private function isListedAsDependencyInRegistry(string $name): bool
    {
        foreach ($this->registry->listComponents() as $component) {
            if (in_array($name, $this->registry->dependencies($component), true)) {
                return true;
            }
        }

        return false;
    }
}
