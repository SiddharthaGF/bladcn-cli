<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Tests\Registry;

use AiluraCode\Bladcn\Config\BladcnConfig;
use AiluraCode\Bladcn\Registry\Registry;
use AiluraCode\Bladcn\Tests\BladcnTestCase;
use RuntimeException;

final class RegistryTest extends BladcnTestCase
{
    public function test_lists_components_from_fixture(): void
    {
        $components = $this->registry()->listComponents();

        $this->assertSame(['accordion', 'button', 'carousel', 'icon', 'sonner'], $components);
    }

    public function test_has_component(): void
    {
        $registry = $this->registry();

        $this->assertTrue($registry->hasComponent('button'));
        $this->assertFalse($registry->hasComponent('missing'));
    }

    public function test_reads_dependencies(): void
    {
        $this->assertSame(['icon'], $this->registry()->dependencies('accordion'));
        $this->assertSame([], $this->registry()->dependencies('button'));
    }

    public function test_component_source_path_for_file_and_directory(): void
    {
        $registry = $this->registry();

        $this->assertSame(
            $this->registryComponentsPath().'/button.blade.php',
            $registry->componentSourcePath('button'),
        );

        $this->assertSame(
            $this->registryComponentsPath().'/icon',
            $registry->componentSourcePath('icon'),
        );
    }

    public function test_is_directory_component(): void
    {
        $registry = $this->registry();

        $this->assertTrue($registry->isDirectoryComponent('icon'));
        $this->assertFalse($registry->isDirectoryComponent('button'));
    }

    public function test_throws_when_local_registry_missing(): void
    {
        $config = BladcnConfig::fromArray([
            'registry' => $this->tempDir().'/missing-registry',
            'resolved' => [],
        ], $this->tempDir());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Local registry not found');

        new Registry($config);
    }

    public function test_throws_when_component_not_found(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not found in registry');

        $this->registry()->componentSourcePath('missing');
    }

    private function registry(): Registry
    {
        $config = BladcnConfig::fromArray([
            'registry' => $this->registryFixturePath(),
            'resolved' => [],
        ], $this->tempDir());

        return new Registry($config);
    }
}
