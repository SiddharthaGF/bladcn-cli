<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Tests\Commands;

use AiluraCode\Bladcn\Commands\AddCommand;
use AiluraCode\Bladcn\Config\BladcnConfig;
use AiluraCode\Bladcn\Tests\BladcnTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class AddCommandTest extends BladcnTestCase
{
    public function test_add_installs_component_with_dependencies(): void
    {
        $this->chdirToTemp();
        $this->writeBladcnJson();

        $tester = new CommandTester(new AddCommand);
        $exitCode = $tester->execute([
            'components' => ['accordion'],
            '--no-external-deps' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('+ icon', $tester->getDisplay());
        $this->assertStringContainsString('+ accordion', $tester->getDisplay());

        $config = BladcnConfig::load($this->tempDir());
        $this->assertEqualsCanonicalizing(['icon', 'accordion'], $config->resolved);
    }

    public function test_add_dry_run_does_not_copy_files(): void
    {
        $this->chdirToTemp();
        $this->writeBladcnJson();

        $tester = new CommandTester(new AddCommand);
        $exitCode = $tester->execute([
            'components' => ['button'],
            '--dry-run' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Dry run', $tester->getDisplay());
        $this->assertFileDoesNotExist(
            $this->tempDir().'/resources/views/components/ui/button.blade.php',
        );
    }

    public function test_add_without_dependencies(): void
    {
        $this->chdirToTemp();
        $this->writeBladcnJson();

        $tester = new CommandTester(new AddCommand);
        $exitCode = $tester->execute([
            'components' => ['accordion'],
            '--no-deps' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);

        $config = BladcnConfig::load($this->tempDir());
        $this->assertSame(['accordion'], $config->resolved);
        $this->assertFileDoesNotExist(
            $this->tempDir().'/resources/views/components/ui/icon/icon.blade.php',
        );
    }

    public function test_add_fails_for_unknown_component(): void
    {
        $this->chdirToTemp();
        $this->writeBladcnJson();

        $tester = new CommandTester(new AddCommand);
        $exitCode = $tester->execute(['components' => ['missing']]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Unknown component', $tester->getDisplay());
    }

    public function test_add_all_installs_every_registry_component(): void
    {
        $this->chdirToTemp();
        $this->writeBladcnJson();

        $tester = new CommandTester(new AddCommand);
        $exitCode = $tester->execute([
            '--all' => true,
            '--no-external-deps' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Installing 3 components', $tester->getDisplay());

        $config = BladcnConfig::load($this->tempDir());
        $this->assertEqualsCanonicalizing(['accordion', 'button', 'icon'], $config->resolved);
    }

    public function test_add_all_conflicts_with_component_names(): void
    {
        $this->chdirToTemp();
        $this->writeBladcnJson();

        $tester = new CommandTester(new AddCommand);
        $exitCode = $tester->execute([
            'components' => ['button'],
            '--all' => true,
        ]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('not both', $tester->getDisplay());
    }
}
