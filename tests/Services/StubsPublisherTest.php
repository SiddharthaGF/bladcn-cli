<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Tests\Services;

use AiluraCode\Bladcn\Services\StubsPublisher;
use AiluraCode\Bladcn\Support\InitOptions;
use AiluraCode\Bladcn\Support\PackagePath;
use AiluraCode\Bladcn\Tests\BladcnTestCase;

final class StubsPublisherTest extends BladcnTestCase
{
    public function test_publish_uses_demo_registry_stubs(): void
    {
        $demoRegistry = PackagePath::demoRegistryNear(PackagePath::root());

        $this->assertNotNull($demoRegistry);

        $projectRoot = $this->tempDir().'/laravel-app';
        mkdir($projectRoot.'/app', 0775, true);
        touch($projectRoot.'/artisan');

        $publisher = new StubsPublisher;
        $written = $publisher->publish($projectRoot, new InitOptions(
            registry: $demoRegistry,
            skipPrompts: true,
        ));

        $this->assertFileExists($projectRoot.'/app/Providers/BladcnServiceProvider.php');
        $this->assertContains('app/Providers/BladcnServiceProvider.php', $written);
    }
}
