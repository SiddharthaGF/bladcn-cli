<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Tests\Installer;

use AiluraCode\Bladcn\Config\BladcnConfig;
use AiluraCode\Bladcn\Installer\ComponentInstaller;
use AiluraCode\Bladcn\Installer\ComponentRemover;
use AiluraCode\Bladcn\Registry\DependencyResolver;
use AiluraCode\Bladcn\Registry\Registry;
use AiluraCode\Bladcn\Tests\BladcnTestCase;
use RuntimeException;

final class ComponentInstallerTest extends BladcnTestCase
{
    public function test_resolve_install_plan_orders_dependencies_first(): void
    {
        $plan = $this->installer()->resolveInstallPlan('accordion');

        $this->assertSame(['icon', 'accordion'], $plan);
    }

    public function test_resolve_install_plan_without_dependencies(): void
    {
        $plan = $this->installer()->resolveInstallPlan('accordion', withDependencies: false);

        $this->assertSame(['accordion'], $plan);
    }

    public function test_install_copies_components_and_skips_dependencies_json(): void
    {
        $installer = $this->installer();
        $result = $installer->install('accordion', installExternal: false);

        $this->assertSame(['icon', 'accordion'], $result['components']);

        $target = $this->tempDir().'/resources/views/components/ui';

        $this->assertFileExists($target.'/icon/icon.blade.php');
        $this->assertFileExists($target.'/accordion/accordion.blade.php');
        $this->assertFileDoesNotExist($target.'/accordion/dependencies.json');

        $config = BladcnConfig::load($this->tempDir());
        $this->assertEqualsCanonicalizing(['icon', 'accordion'], $config->resolved);
    }

    public function test_install_with_external_dependencies_disabled(): void
    {
        $this->writeComposerJson();

        $result = $this->installer()->install('accordion', installExternal: false);

        $this->assertSame([], $result['composer']);
        $this->assertSame(['icon', 'accordion'], $result['components']);
    }

    public function test_install_skips_existing_without_overwrite(): void
    {
        $installer = $this->installer();
        $installer->install('button', installExternal: false);

        $result = $installer->install('button', installExternal: false);

        $this->assertSame([], $result['components']);
    }

    public function test_install_overwrites_existing_component(): void
    {
        $installer = $this->installer();
        $target = $this->tempDir().'/resources/views/components/ui/button.blade.php';

        $installer->install('button', installExternal: false);
        file_put_contents($target, 'stale');

        $result = $installer->install('button', overwrite: true, installExternal: false);

        $this->assertSame(['button'], $result['components']);
        $this->assertStringContainsString('<button>', (string) file_get_contents($target));
    }

    public function test_resolve_install_plan_throws_for_unknown_component(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unknown component');

        $this->installer()->resolveInstallPlan('missing');
    }

    public function test_is_installed_detects_file_and_directory_components(): void
    {
        $installer = $this->installer();

        $this->assertFalse($installer->isInstalled('button'));
        $this->assertFalse($installer->isInstalled('icon'));

        $installer->install('accordion', installExternal: false);

        $this->assertFalse($installer->isInstalled('button'));
        $this->assertTrue($installer->isInstalled('icon'));
        $this->assertTrue($installer->isInstalled('accordion'));
    }

    public function test_install_publishes_sonner_css_asset(): void
    {
        $this->createProjectAssets();

        $result = $this->installer()->install('sonner', installExternal: false);

        $this->assertContains('sonner', $result['components']);
        $this->assertFileExists($this->tempDir().'/resources/css/sonner.css');

        $appCss = (string) file_get_contents($this->tempDir().'/resources/css/app.css');
        $this->assertStringContainsString('@import "./sonner.css";', $appCss);
    }

    public function test_install_publishes_carousel_js_asset(): void
    {
        $this->createProjectAssets();

        $result = $this->installer()->install('carousel', installExternal: false);

        $this->assertContains('carousel', $result['components']);
        $this->assertFileExists($this->tempDir().'/resources/js/bladcn/carousel.js');

        $bladcnJs = (string) file_get_contents($this->tempDir().'/resources/js/bladcn.js');
        $this->assertStringContainsString('bladcn/carousel.js', $bladcnJs);
        $this->assertStringContainsString('registerBladcnCarousel();', $bladcnJs);
    }

    public function test_install_publishes_sonner_css_when_component_already_present(): void
    {
        $this->createProjectAssets();
        mkdir($this->tempDir().'/resources/views/components/ui/icon', 0775, true);
        mkdir($this->tempDir().'/resources/views/components/ui/sonner', 0775, true);
        file_put_contents($this->tempDir().'/resources/views/components/ui/icon/icon.blade.php', '<span></span>');
        file_put_contents($this->tempDir().'/resources/views/components/ui/sonner/index.blade.php', '<div></div>');

        $result = $this->installer()->install('sonner', installExternal: false);

        $this->assertSame([], $result['components']);
        $this->assertFileExists($this->tempDir().'/resources/css/sonner.css');

        $appCss = (string) file_get_contents($this->tempDir().'/resources/css/app.css');
        $this->assertStringContainsString('@import "./sonner.css";', $appCss);
        $this->assertNotEmpty($result['assets']);
    }

    public function test_remove_sonner_removes_css_asset(): void
    {
        $this->createProjectAssets();
        $installer = $this->installer();
        $installer->install('sonner', installExternal: false);

        $remover = new ComponentRemover(
            BladcnConfig::load($this->tempDir()),
            new DependencyResolver(new Registry(BladcnConfig::load($this->tempDir()))),
        );
        $remover->removeNames(['sonner']);

        $this->assertFileDoesNotExist($this->tempDir().'/resources/css/sonner.css');

        $appCss = (string) file_get_contents($this->tempDir().'/resources/css/app.css');
        $this->assertStringNotContainsString('@import "./sonner.css";', $appCss);
    }

    private function createProjectAssets(): void
    {
        mkdir($this->tempDir().'/resources/css', 0775, true);
        mkdir($this->tempDir().'/resources/js', 0775, true);
        file_put_contents($this->tempDir().'/resources/css/app.css', '@import "tailwindcss";'.PHP_EOL);
        copy(
            $this->registryFixturePath().'/resources/js/bladcn.js',
            $this->tempDir().'/resources/js/bladcn.js',
        );
    }

    private function installer(): ComponentInstaller
    {
        $config = BladcnConfig::fromArray([
            'registry' => $this->registryFixturePath(),
            'resolved' => [],
        ], $this->tempDir());

        return new ComponentInstaller($config, new Registry($config));
    }
}
