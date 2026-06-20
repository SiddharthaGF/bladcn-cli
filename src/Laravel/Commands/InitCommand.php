<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Laravel\Commands;

use AiluraCode\Bladcn\Config\BladcnConfig;
use AiluraCode\Bladcn\Services\InitService;
use AiluraCode\Bladcn\Support\InitOptions;
use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\Application;
use Override;
use Symfony\Component\Console\Style\SymfonyStyle;

final class InitCommand extends Command
{
    #[Override]
    protected $signature = 'bladcn:init
        {--path=resources/views/components/ui : Target path for components}
        {--registry= : Local registry, github:owner/repo, or GitHub URL}
        {--branch=main : GitHub registry branch}
        {--force : Overwrite existing files}
        {--with-dark-mode : Include CSS variables for dark mode}
        {--css-file=app.css : Main CSS file (relative to resources/css/)}
        {--theme-file=bladcn-theme.css : CSS theme file}
        {--skip-prompts : Skip interactive prompts}
        {--skip-assets : Only create bladcn.json, do not publish stubs}';

    #[Override]
    protected $description = 'Initialize bladcn.json and base assets in a Laravel project';

    public function handle(InitService $initService, Application $app): int
    {
        $options = InitOptions::fromInput(
            $this->input,
            BladcnConfig::defaultRegistryForInit($app->basePath()),
        );
        $io = new SymfonyStyle($this->input, $this->output);

        return $initService->run($app->basePath(), $options, $io, $this->input);
    }
}
