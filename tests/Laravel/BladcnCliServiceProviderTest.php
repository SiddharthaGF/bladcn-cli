<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Tests\Laravel;

use AiluraCode\Bladcn\Config\BladcnConfig;
use AiluraCode\Bladcn\Laravel\BladcnCliServiceProvider;
use Orchestra\Testbench\TestCase;
use Override;

final class BladcnCliServiceProviderTest extends TestCase
{
    public function test_merges_package_config(): void
    {
        $this->assertSame('../bladcn-components', config('bladcn.registry'));
        $this->assertSame('main', config('bladcn.registry_branch'));
        $this->assertSame('resources/views/components/ui', config('bladcn.components_path'));
    }

    public function test_default_registry_reads_laravel_config(): void
    {
        config(['bladcn.registry' => '../bladcn-components']);

        $this->assertSame('../bladcn-components', BladcnConfig::defaultRegistry());
    }

    public function test_config_can_be_published(): void
    {
        $this->artisan('vendor:publish', ['--tag' => 'bladcn-config', '--force' => true]);

        $this->assertFileExists(config_path('bladcn.php'));
    }

    #[Override]
    protected function getPackageProviders($app): array
    {
        return [BladcnCliServiceProvider::class];
    }
}
