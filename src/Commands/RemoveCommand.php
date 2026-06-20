<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Commands;

use AiluraCode\Bladcn\Config\BladcnConfig;
use AiluraCode\Bladcn\Installer\ComponentRemover;
use AiluraCode\Bladcn\Registry\DependencyResolver;
use AiluraCode\Bladcn\Registry\Registry;
use AiluraCode\Bladcn\Support\ConsoleInput;
use AiluraCode\Bladcn\Support\ProjectPaths;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(name: 'remove', description: 'Remove components and orphan dependencies')]
final class RemoveCommand extends Command
{
    #[Override]
    protected function configure(): void
    {
        $this
            ->addArgument('components', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Component names')
            ->addOption('no-orphans', null, InputOption::VALUE_NONE, 'Do not remove orphan internal dependencies')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Remove orphan dependencies without prompting')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show removal plan without deleting files');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $config = BladcnConfig::load(ProjectPaths::cwdOrDot());
            $registry = new Registry($config);
            $remover = new ComponentRemover($config, new DependencyResolver($registry));
        } catch (Throwable $throwable) {
            $io->error($throwable->getMessage());

            return Command::FAILURE;
        }

        $components = ConsoleInput::stringListArgument($input, 'components');
        $withOrphans = ! ConsoleInput::boolOption($input, 'no-orphans');
        $autoConfirm = ConsoleInput::boolOption($input, 'yes');
        $dryRun = ConsoleInput::boolOption($input, 'dry-run');

        try {
            $plan = $remover->planRemoval($components, $withOrphans);
        } catch (Throwable $throwable) {
            $io->error($throwable->getMessage());

            return Command::FAILURE;
        }

        $toRemove = $components;

        $io->section('Removal plan');

        foreach ($components as $component) {
            $io->writeln('  - '.$component);
        }

        if ($withOrphans && $plan['orphans'] !== []) {
            $io->writeln('');
            $io->writeln('Orphan internal dependencies:');
            foreach ($plan['orphans'] as $orphan) {
                $io->writeln('  - '.$orphan);
            }

            if ($autoConfirm) {
                $toRemove = $plan['removed'];
            } elseif ($input->isInteractive() && ! $dryRun) {
                if ($io->confirm('Remove orphan dependencies?', true)) {
                    $toRemove = $plan['removed'];
                }
            } elseif ($dryRun) {
                $toRemove = $plan['removed'];
            }
        }

        if ($plan['composerOrphans'] !== []) {
            $io->writeln('');
            $io->note([
                'Composer packages you may remove manually:',
                '  composer remove '.implode(' ', $plan['composerOrphans']),
            ]);
        }

        if ($dryRun) {
            $io->success('Dry run completed.');

            return Command::SUCCESS;
        }

        try {
            $result = $remover->removeNames($toRemove);
        } catch (Throwable $throwable) {
            $io->error($throwable->getMessage());

            return Command::FAILURE;
        }

        foreach ($result['removed'] as $name) {
            $io->writeln('  <info>-</info> '.$name);
        }

        if ($result['removed'] === []) {
            $io->note('Nothing to remove.');
        } else {
            $io->success(sprintf('Removed %d component(s).', count($result['removed'])));
        }

        return Command::SUCCESS;
    }
}
