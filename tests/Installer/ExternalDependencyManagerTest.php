<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Tests\Installer;

use AiluraCode\Bladcn\Installer\ExternalDependencyManager;
use AiluraCode\Bladcn\Tests\BladcnTestCase;

final class ExternalDependencyManagerTest extends BladcnTestCase
{
    public function test_missing_composer_packages_detects_absent_require(): void
    {
        $this->writeComposerJson(['php' => '^8.2']);

        $manager = new ExternalDependencyManager;
        $missing = $manager->missingComposerPackages(
            $this->tempDir(),
            ['mallardduck/blade-lucide-icons'],
        );

        $this->assertSame(['mallardduck/blade-lucide-icons'], $missing);
    }

    public function test_missing_composer_packages_skips_installed(): void
    {
        $this->writeComposerJson(['mallardduck/blade-lucide-icons' => '^1.0']);

        $manager = new ExternalDependencyManager;
        $missing = $manager->missingComposerPackages(
            $this->tempDir(),
            ['mallardduck/blade-lucide-icons'],
        );

        $this->assertSame([], $missing);
    }
}
