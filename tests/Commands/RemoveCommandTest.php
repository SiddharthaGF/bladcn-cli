<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Tests\Commands;

use AiluraCode\Bladcn\Commands\RemoveCommand;
use AiluraCode\Bladcn\Config\BladcnConfig;
use AiluraCode\Bladcn\Tests\BladcnTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class RemoveCommandTest extends BladcnTestCase
{
    public function test_remove_component_with_orphans(): void
    {
        $this->chdirToTemp();
        $this->writeBladcnJson(['resolved' => ['icon', 'accordion']]);
        $this->seedComponent('icon');
        $this->seedComponent('accordion');

        $tester = new CommandTester(new RemoveCommand);
        $exitCode = $tester->execute([
            'components' => ['accordion'],
            '--yes' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('- accordion', $tester->getDisplay());
        $this->assertStringContainsString('- icon', $tester->getDisplay());

        $config = BladcnConfig::load($this->tempDir());
        $this->assertSame([], $config->resolved);
    }

    private function seedComponent(string $name): void
    {
        $target = $this->tempDir().'/resources/views/components/ui';
        if (! is_dir($target)) {
            mkdir($target, 0775, true);
        }

        if ($name === 'button') {
            copy($this->registryComponentsPath().'/button.blade.php', $target.'/button.blade.php');

            return;
        }

        $this->copyDirectory($this->registryComponentsPath().'/'.$name, $target.'/'.$name);
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
