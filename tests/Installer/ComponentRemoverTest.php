<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Tests\Installer;

use AiluraCode\Bladcn\Config\BladcnConfig;
use AiluraCode\Bladcn\Installer\ComponentRemover;
use AiluraCode\Bladcn\Registry\DependencyResolver;
use AiluraCode\Bladcn\Registry\Registry;
use AiluraCode\Bladcn\Tests\BladcnTestCase;

final class ComponentRemoverTest extends BladcnTestCase
{
    public function test_remove_component_and_orphan_dependency(): void
    {
        $this->seedInstalledComponents(['icon', 'accordion']);

        $remover = $this->remover(['icon', 'accordion']);
        $plan = $remover->planRemoval(['accordion']);

        $this->assertEqualsCanonicalizing(['accordion', 'icon'], $plan['removed']);
        $this->assertSame(['icon'], $plan['orphans']);

        $result = $remover->removeNames($plan['removed']);

        $this->assertEqualsCanonicalizing(['accordion', 'icon'], $result['removed']);
        $this->assertFileDoesNotExist($this->componentPath('accordion/accordion.blade.php'));
        $this->assertFileDoesNotExist($this->componentPath('icon/icon.blade.php'));

        $config = BladcnConfig::load($this->tempDir());
        $this->assertSame([], $config->resolved);
    }

    public function test_remove_keeps_shared_dependency(): void
    {
        $this->writeComposerJson(['mallardduck/blade-lucide-icons' => '^1.0']);
        $this->seedInstalledComponents(['icon', 'accordion', 'button']);

        $remover = $this->remover(['icon', 'accordion', 'button']);
        $result = $remover->removeNames(['button']);

        $this->assertSame(['button'], $result['removed']);
        $this->assertSame([], $result['composerOrphans']);
        $this->assertFileExists($this->componentPath('icon/icon.blade.php'));
    }

    /**
     * @param  list<string>  $resolved
     */
    private function remover(array $resolved = []): ComponentRemover
    {
        $config = BladcnConfig::fromArray([
            'registry' => $this->registryFixturePath(),
            'resolved' => $resolved,
        ], $this->tempDir());

        $registry = new Registry($config);

        return new ComponentRemover($config, new DependencyResolver($registry));
    }

    /** @param list<string> $components */
    private function seedInstalledComponents(array $components): void
    {
        $target = $this->tempDir().'/resources/views/components/ui';
        mkdir($target, 0775, true);

        foreach ($components as $name) {
            if ($name === 'button') {
                copy($this->registryComponentsPath().'/button.blade.php', $target.'/button.blade.php');

                continue;
            }

            $this->copyDirectory($this->registryComponentsPath().'/'.$name, $target.'/'.$name);
        }

        $json = json_encode([
            'registry' => $this->registryFixturePath(),
            'resolved' => $components,
        ], JSON_PRETTY_PRINT);

        file_put_contents($this->tempDir().'/bladcn.json', $json.PHP_EOL);
    }

    private function componentPath(string $relative): string
    {
        return $this->tempDir().'/resources/views/components/ui/'.$relative;
    }

    private function copyDirectory(string $source, string $destination): void
    {
        mkdir($destination, 0775, true);

        $entries = scandir($source);

        foreach ($entries !== false ? $entries : [] as $entry) {
            if ($entry === '.') {
                continue;
            }

            if ($entry === '..') {
                continue;
            }

            $from = $source.'/'.$entry;
            $to = $destination.'/'.$entry;

            if (is_dir($from)) {
                $this->copyDirectory($from, $to);
            } else {
                copy($from, $to);
            }
        }
    }
}
