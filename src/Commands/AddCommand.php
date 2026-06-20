<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Commands;

use AiluraCode\Bladcn\Config\BladcnConfig;
use AiluraCode\Bladcn\Installer\ComponentInstaller;
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

#[AsCommand(name: 'add', description: 'Add Blade components and their dependencies')]
final class AddCommand extends Command
{
    #[Override]
    protected function configure(): void
    {
        $this
            ->addArgument('components', InputArgument::IS_ARRAY, 'Component names (use --all for every registry component)')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Install every component from the registry')
            ->addOption('overwrite', 'o', InputOption::VALUE_NONE, 'Overwrite existing components')
            ->addOption('no-deps', null, InputOption::VALUE_NONE, 'Skip internal dependencies')
            ->addOption('no-external-deps', null, InputOption::VALUE_NONE, 'Skip Composer packages from the registry')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show install plan without copying files');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $config = BladcnConfig::load(ProjectPaths::cwdOrDot());
            $registry = new Registry($config);
            $installer = new ComponentInstaller($config, $registry);
        } catch (Throwable $throwable) {
            $io->error($throwable->getMessage());

            return Command::FAILURE;
        }

        $components = ConsoleInput::stringListArgument($input, 'components');
        $installAll = ConsoleInput::boolOption($input, 'all');
        $withDependencies = ! ConsoleInput::boolOption($input, 'no-deps');
        $installExternal = ! ConsoleInput::boolOption($input, 'no-external-deps');
        $overwrite = ConsoleInput::boolOption($input, 'overwrite');
        $dryRun = ConsoleInput::boolOption($input, 'dry-run');

        if ($installAll && $components !== []) {
            $io->error('Provide component names or --all, not both.');

            return Command::FAILURE;
        }

        if ($installAll) {
            $components = $registry->listComponents();

            if ($components === []) {
                $io->warning('Registry has no components.');

                return Command::SUCCESS;
            }

            $io->writeln(sprintf(
                'Installing <info>%d</info> components from the registry...',
                count($components),
            ));
        } elseif ($components === []) {
            $io->error('Provide component names or use --all.');

            return Command::FAILURE;
        }

        $allInstalled = [];

        foreach ($components as $component) {
            try {
                $plan = $installer->resolveInstallPlan($component, $withDependencies);
            } catch (Throwable $e) {
                $io->error($e->getMessage());

                return Command::FAILURE;
            }

            $io->section('Component: '.$component);

            if ($withDependencies && count($plan) > 1) {
                $io->writeln('Install plan:');
                foreach ($plan as $item) {
                    $suffix = $installer->isInstalled($item) ? ' (already installed)' : '';
                    $io->writeln(sprintf('  - %s%s', $item, $suffix));
                }
            }

            try {
                $result = $installer->install(
                    $component,
                    $overwrite,
                    $withDependencies,
                    $installExternal,
                    $dryRun,
                );
            } catch (Throwable $e) {
                $io->error($e->getMessage());

                return Command::FAILURE;
            }

            if ($result['components'] === [] && $result['composer'] === [] && $result['assets'] === []) {
                if (! $dryRun) {
                    $io->note('Nothing new to install (already present). Use --overwrite to replace.');
                }
            } else {
                foreach ($result['components'] as $name) {
                    $prefix = $dryRun ? '~' : '+';
                    $io->writeln(sprintf('  <info>%s</info> %s', $prefix, $name));
                }

                foreach ($result['composer'] as $package) {
                    $prefix = $dryRun ? '~' : '+';
                    $io->writeln(sprintf('  <info>%s</info> composer: %s', $prefix, $package));
                }

                foreach ($result['assets'] as $asset) {
                    $prefix = $dryRun ? '~' : '+';
                    $io->writeln(sprintf('  <info>%s</info> asset: %s', $prefix, $asset));
                }
            }

            $allInstalled = [...$allInstalled, ...$result['components']];
        }

        if ($dryRun) {
            $io->success('Dry run completed.');

            return Command::SUCCESS;
        }

        $allInstalled = array_values(array_unique($allInstalled));

        if ($allInstalled !== []) {
            $io->success(sprintf('Installed %d component(s).', count($allInstalled)));
        }

        return Command::SUCCESS;
    }
}
