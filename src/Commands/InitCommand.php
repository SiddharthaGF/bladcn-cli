<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Commands;

use AiluraCode\Bladcn\Config\BladcnConfig;
use AiluraCode\Bladcn\Services\InitService;
use AiluraCode\Bladcn\Support\InitOptions;
use AiluraCode\Bladcn\Support\ProjectPaths;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'init', description: 'Initialize bladcn.json and base assets in a Laravel project')]
final class InitCommand extends Command
{
    public function __construct(
        private readonly InitService $initService = new InitService,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Target path for components', BladcnConfig::defaultComponentsPath())
            ->addOption(
                'registry',
                null,
                InputOption::VALUE_REQUIRED,
                'Local registry, github:owner/repo, or GitHub URL',
                BladcnConfig::defaultRegistry(),
            )
            ->addOption('branch', null, InputOption::VALUE_REQUIRED, 'GitHub registry branch', BladcnConfig::defaultRegistryBranch())
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing files')
            ->addOption('with-dark-mode', null, InputOption::VALUE_NONE, 'Include CSS variables for dark mode')
            ->addOption('css-file', null, InputOption::VALUE_REQUIRED, 'Main CSS file (relative to resources/css/)', BladcnConfig::defaultCssFile())
            ->addOption('theme-file', null, InputOption::VALUE_REQUIRED, 'CSS theme file', BladcnConfig::defaultThemeFile())
            ->addOption('skip-prompts', null, InputOption::VALUE_NONE, 'Skip interactive prompts')
            ->addOption('skip-assets', null, InputOption::VALUE_NONE, 'Only create bladcn.json, do not publish stubs');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $projectRoot = ProjectPaths::cwdOrDot();
        $options = InitOptions::fromInput($input, BladcnConfig::defaultRegistryForInit($projectRoot));

        return $this->initService->run($projectRoot, $options, $io, $input);
    }
}
