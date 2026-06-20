<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Tests\Commands;

use AiluraCode\Bladcn\Commands\InitCommand;
use AiluraCode\Bladcn\Config\BladcnConfig;
use AiluraCode\Bladcn\Tests\BladcnTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class InitCommandTest extends BladcnTestCase
{
    public function test_init_creates_bladcn_json(): void
    {
        $this->chdirToTemp();

        $tester = new CommandTester(new InitCommand);
        $exitCode = $tester->execute([
            '--skip-prompts' => true,
            '--skip-assets' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertFileExists(BladcnConfig::path($this->tempDir()));

        $written = json_decode((string) file_get_contents(BladcnConfig::path($this->tempDir())), true);
        $this->assertIsArray($written);
        $this->assertArrayHasKey('registry', $written);
        $this->assertSame('../bladcn-components', $written['registry']);
    }

    public function test_init_uses_bladcn_registry_env_when_set(): void
    {
        $this->chdirToTemp();
        putenv('BLADCN_REGISTRY=../bladcn-components');

        try {
            $tester = new CommandTester(new InitCommand);
            $exitCode = $tester->execute([
                '--skip-prompts' => true,
                '--skip-assets' => true,
            ]);

            $this->assertSame(Command::SUCCESS, $exitCode);

            $written = json_decode((string) file_get_contents(BladcnConfig::path($this->tempDir())), true);
            $this->assertIsArray($written);
            $this->assertSame('../bladcn-components', $written['registry']);
        } finally {
            putenv('BLADCN_REGISTRY');
        }
    }

    public function test_init_with_local_registry(): void
    {
        $this->chdirToTemp();

        $tester = new CommandTester(new InitCommand);
        $exitCode = $tester->execute([
            '--registry' => $this->registryFixturePath(),
            '--skip-prompts' => true,
            '--skip-assets' => true,
            '--force' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);

        $config = BladcnConfig::load($this->tempDir());
        $this->assertSame(realpath($this->registryFixturePath()), $config->registryPath);
    }

    public function test_init_fails_when_config_exists_without_force(): void
    {
        $this->chdirToTemp();
        $this->writeBladcnJson();

        $tester = new CommandTester(new InitCommand);
        $exitCode = $tester->execute([
            '--skip-prompts' => true,
            '--skip-assets' => true,
        ]);

        $this->assertSame(Command::FAILURE, $exitCode);
    }
}
