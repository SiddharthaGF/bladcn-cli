<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Commands;

use AiluraCode\Bladcn\Config\BladcnConfig;
use AiluraCode\Bladcn\Registry\Registry;
use AiluraCode\Bladcn\Support\ProjectPaths;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(name: 'list', description: 'List components available in the registry')]
final class ListCommand extends Command
{
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $config = BladcnConfig::load(ProjectPaths::cwdOrDot());
            $registry = new Registry($config);
        } catch (Throwable $throwable) {
            $io->warning($throwable->getMessage());
            $io->note('Run `bladcn init` to create bladcn.json.');

            return Command::FAILURE;
        }

        $components = $registry->listComponents();
        $table = new Table($output);
        $table->setHeaders(['Component', 'Dependencies', 'Installed']);

        $targetRoot = $config->componentsAbsolutePath();

        foreach ($components as $name) {
            $deps = $registry->dependencies($name);
            $installed = $this->isInstalled($targetRoot, $name) ? 'yes' : 'no';

            $table->addRow([
                $name,
                $deps === [] ? '—' : implode(', ', $deps),
                $installed,
            ]);
        }

        $table->render();
        $io->writeln('');
        $io->writeln(sprintf('<info>%d</info> components in the registry.', count($components)));

        return Command::SUCCESS;
    }

    private function isInstalled(string $targetRoot, string $name): bool
    {
        return is_dir($targetRoot.DIRECTORY_SEPARATOR.$name)
            || is_file($targetRoot.DIRECTORY_SEPARATOR.$name.'.blade.php');
    }
}
