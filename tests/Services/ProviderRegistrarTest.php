<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Tests\Services;

use AiluraCode\Bladcn\Services\ProviderRegistrar;
use AiluraCode\Bladcn\Tests\BladcnTestCase;

final class ProviderRegistrarTest extends BladcnTestCase
{
    public function test_registers_provider_in_bootstrap_file(): void
    {
        mkdir($this->tempDir().'/bootstrap', 0775, true);
        file_put_contents($this->tempDir().'/bootstrap/providers.php', <<<'PHP'
<?php

return [
    App\Providers\AppServiceProvider::class,
];
PHP);

        $registrar = new ProviderRegistrar;
        $registered = $registrar->register($this->tempDir());

        $this->assertTrue($registered);
        $content = (string) file_get_contents($this->tempDir().'/bootstrap/providers.php');
        $this->assertStringContainsString('App\Providers\BladcnServiceProvider::class', $content);
    }

    public function test_skips_when_provider_already_registered(): void
    {
        mkdir($this->tempDir().'/bootstrap', 0775, true);
        file_put_contents($this->tempDir().'/bootstrap/providers.php', <<<'PHP'
<?php

return [
    App\Providers\BladcnServiceProvider::class,
];
PHP);

        $registrar = new ProviderRegistrar;
        $registered = $registrar->register($this->tempDir());

        $this->assertFalse($registered);
    }
}
