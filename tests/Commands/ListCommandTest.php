<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Tests\Commands;

use AiluraCode\Bladcn\Commands\ListCommand;
use AiluraCode\Bladcn\Tests\BladcnTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ListCommandTest extends BladcnTestCase
{
    public function test_list_shows_components_from_registry(): void
    {
        $this->chdirToTemp();
        $this->writeBladcnJson();

        $tester = new CommandTester(new ListCommand);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $output = $tester->getDisplay();

        $this->assertStringContainsString('accordion', $output);
        $this->assertStringContainsString('button', $output);
        $this->assertStringContainsString('icon', $output);
        $this->assertStringContainsString('5 components', $output);
    }

    public function test_list_fails_without_config(): void
    {
        $this->chdirToTemp();

        $tester = new CommandTester(new ListCommand);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('bladcn init', $tester->getDisplay());
    }
}
