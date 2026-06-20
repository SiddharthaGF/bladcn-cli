<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Tests\Services;

use AiluraCode\Bladcn\Services\InitService;
use AiluraCode\Bladcn\Support\InitOptions;
use AiluraCode\Bladcn\Tests\BladcnTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

final class InitServiceTest extends BladcnTestCase
{
    public function test_run_creates_config_and_publishes_laravel_stubs(): void
    {
        $this->createLaravelProjectSkeleton();

        $options = new InitOptions(
            registry: $this->registryFixturePath(),
            skipPrompts: true,
            withDarkMode: true,
        );

        $exitCode = $this->runInit($options);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertFileExists($this->tempDir().'/bladcn.json');
        $this->assertFileExists($this->tempDir().'/app/Providers/BladcnServiceProvider.php');
        $this->assertFileExists($this->tempDir().'/resources/views/partials/bladcn-boot.blade.php');
        $this->assertFileExists($this->tempDir().'/resources/css/bladcn-theme.css');
        $this->assertFileExists($this->tempDir().'/resources/css/bladcn-base.css');
        $this->assertFileExists($this->tempDir().'/resources/css/sonner.css');
        $this->assertFileExists($this->tempDir().'/resources/js/bladcn.js');
        $this->assertFileExists($this->tempDir().'/resources/js/bladcn/carousel.js');

        $providers = (string) file_get_contents($this->tempDir().'/bootstrap/providers.php');
        $this->assertStringContainsString('BladcnServiceProvider::class', $providers);

        $appCss = (string) file_get_contents($this->tempDir().'/resources/css/app.css');
        $this->assertStringContainsString('@import "./sonner.css";', $appCss);
        $this->assertStringContainsString('@import "./bladcn-base.css";', $appCss);
        $this->assertStringContainsString('@import "./bladcn-theme.css";', $appCss);

        $appJs = (string) file_get_contents($this->tempDir().'/resources/js/app.js');
        $this->assertStringContainsString("import './bladcn';", $appJs);
    }

    public function test_run_skips_assets_when_requested(): void
    {
        $this->createLaravelProjectSkeleton();

        $options = new InitOptions(
            registry: $this->registryFixturePath(),
            skipPrompts: true,
            publishAssets: false,
        );

        $exitCode = $this->runInit($options);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertFileExists($this->tempDir().'/bladcn.json');
        $this->assertFileDoesNotExist($this->tempDir().'/app/Providers/BladcnServiceProvider.php');
    }

    public function test_run_without_laravel_only_creates_config(): void
    {
        $options = new InitOptions(
            registry: $this->registryFixturePath(),
            skipPrompts: true,
        );

        $exitCode = $this->runInit($options);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertFileExists($this->tempDir().'/bladcn.json');
        $this->assertFileDoesNotExist($this->tempDir().'/app/Providers/BladcnServiceProvider.php');
    }

    public function test_run_publishes_app_css_when_missing(): void
    {
        $this->createLaravelProjectSkeleton(withAppCss: false);

        $options = new InitOptions(
            registry: $this->registryFixturePath(),
            skipPrompts: true,
            withDarkMode: true,
        );

        $exitCode = $this->runInit($options);

        $this->assertSame(Command::SUCCESS, $exitCode);

        $appCss = (string) file_get_contents($this->tempDir().'/resources/css/app.css');
        $this->assertStringContainsString('@import "tailwindcss";', $appCss);
        $this->assertStringContainsString("@source '../views';", $appCss);
        $this->assertStringContainsString('@import "./sonner.css";', $appCss);
        $this->assertStringContainsString('@import "./bladcn-base.css";', $appCss);
        $this->assertStringContainsString('@import "./bladcn-theme.css";', $appCss);
    }

    private function runInit(InitOptions $options): int
    {
        $input = new ArrayInput([]);
        $output = new BufferedOutput;
        $io = new SymfonyStyle($input, $output);

        return (new InitService)->run($this->tempDir(), $options, $io, $input);
    }

    private function createLaravelProjectSkeleton(bool $withAppCss = true): void
    {
        touch($this->tempDir().'/artisan');
        mkdir($this->tempDir().'/app', 0775, true);
        mkdir($this->tempDir().'/bootstrap', 0775, true);
        mkdir($this->tempDir().'/resources/css', 0775, true);

        file_put_contents($this->tempDir().'/bootstrap/providers.php', <<<'PHP'
<?php

return [
    App\Providers\AppServiceProvider::class,
];
PHP);

        if ($withAppCss) {
            file_put_contents($this->tempDir().'/resources/css/app.css', '@tailwind base;');
            mkdir($this->tempDir().'/resources/js', 0775, true);
            file_put_contents($this->tempDir().'/resources/js/app.js', '// app entry');
        }
    }
}
