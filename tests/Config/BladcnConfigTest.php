<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Tests\Config;

use AiluraCode\Bladcn\Config\BladcnConfig;
use AiluraCode\Bladcn\Support\PackagePath;
use AiluraCode\Bladcn\Tests\BladcnTestCase;
use RuntimeException;

final class BladcnConfigTest extends BladcnTestCase
{
    public function test_from_array_with_package_registry(): void
    {
        $demoRegistry = PackagePath::demoRegistryNear(PackagePath::root());

        $this->assertNotNull($demoRegistry);

        $config = BladcnConfig::fromArray([
            'registry' => 'package:ailuracode/bladcn',
            'resolved' => [],
        ], $this->tempDir());

        $this->assertNull($config->registryRepo);
        $this->assertSame(realpath($demoRegistry), $config->registryPath);
    }

    public function test_registry_string_for_json_resolves_local_demo_registry(): void
    {
        $demoRegistry = PackagePath::demoRegistryNear(PackagePath::root());

        $this->assertNotNull($demoRegistry);

        $registry = BladcnConfig::registryStringForJson(
            'package:ailuracode/bladcn',
            $this->tempDir(),
        );

        $this->assertSame(realpath($demoRegistry), realpath($registry));
    }

    public function test_registry_string_for_json_keeps_github_reference(): void
    {
        $this->assertSame(
            'github:owner/repo',
            BladcnConfig::registryStringForJson('github:owner/repo', $this->tempDir()),
        );
    }

    public function test_from_array_with_github_registry(): void
    {
        $config = BladcnConfig::fromArray([
            'registry' => 'github:ailuracode/bladcn-components',
            'resolved' => [],
        ], $this->tempDir());

        $this->assertSame('ailuracode/bladcn-components', $config->registryRepo);
        $this->assertNull($config->registryPath);
        $this->assertSame('main', $config->registryBranch);
    }

    public function test_from_array_with_github_url(): void
    {
        $config = BladcnConfig::fromArray([
            'registry' => 'https://github.com/ailuracode/bladcn-components',
            'resolved' => [],
        ], $this->tempDir());

        $this->assertSame('ailuracode/bladcn-components', $config->registryRepo);
    }

    public function test_from_array_extracts_branch_from_github_tree_url(): void
    {
        $config = BladcnConfig::fromArray([
            'registry' => 'https://github.com/user/repo/tree/develop',
            'resolved' => [],
        ], $this->tempDir());

        $this->assertSame('user/repo', $config->registryRepo);
        $this->assertSame('develop', $config->registryBranch);
    }

    public function test_from_array_with_local_registry_path(): void
    {
        $config = BladcnConfig::fromArray([
            'registry' => $this->registryFixturePath(),
            'resolved' => [],
        ], $this->tempDir());

        $this->assertNull($config->registryRepo);
        $this->assertSame(realpath($this->registryFixturePath()), $config->registryPath);
    }

    public function test_load_and_save_roundtrip(): void
    {
        $this->writeBladcnJson([
            'resolved' => ['button'],
        ]);

        $loaded = BladcnConfig::load($this->tempDir());
        $loaded = $loaded->withResolved(['button', 'icon']);
        $loaded->save();

        $again = BladcnConfig::load($this->tempDir());

        $this->assertSame(['button', 'icon'], $again->resolved);
        $this->assertFileExists($this->tempDir().'/bladcn.json');
    }

    public function test_load_throws_when_config_missing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('bladcn.json');

        BladcnConfig::load($this->tempDir());
    }

    public function test_empty_github_registry_throws(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('github:');

        BladcnConfig::fromArray([
            'registry' => 'github:',
            'resolved' => [],
        ], $this->tempDir());
    }

    public function test_to_array_serializes_github_registry(): void
    {
        $config = BladcnConfig::fromArray([
            'registry' => 'github:owner/repo',
            'registryBranch' => 'main',
            'resolved' => ['button'],
        ], $this->tempDir());

        $array = $config->toArray();

        $this->assertSame('github:owner/repo', $array['registry']);
        $this->assertSame('main', $array['registryBranch']);
        $this->assertSame(['button'], $array['resolved']);
    }

    public function test_components_absolute_path(): void
    {
        $config = BladcnConfig::fromArray([
            'componentsPath' => 'resources/views/components/ui',
            'registry' => 'github:owner/repo',
            'resolved' => [],
        ], $this->tempDir());

        $expected = $this->tempDir().'/resources/views/components/ui';

        $this->assertSame($expected, $config->componentsAbsolutePath());
    }
}
