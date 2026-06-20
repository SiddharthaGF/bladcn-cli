<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Services;

use AiluraCode\Bladcn\Config\BladcnConfig;
use AiluraCode\Bladcn\Support\ConsoleInput;
use AiluraCode\Bladcn\Support\InitOptions;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final readonly class InitService
{
    public function __construct(
        private StubsPublisher $stubsPublisher = new StubsPublisher,
        private ProviderRegistrar $providerRegistrar = new ProviderRegistrar,
    ) {}

    public function run(string $projectRoot, InitOptions $options, SymfonyStyle $io, InputInterface $input): int
    {
        $options = $this->resolveInteractiveOptions($options, $io, $input);

        $configPath = BladcnConfig::path($projectRoot);

        if (is_file($configPath) && ! $options->force) {
            $io->error('bladcn.json already exists. Use --force to overwrite.');

            return Command::FAILURE;
        }

        $this->writeConfig($projectRoot, $options);

        $io->success('bladcn.json created.');

        if ($options->publishAssets && $this->isLaravelProject($projectRoot)) {
            $published = $this->stubsPublisher->publish($projectRoot, $options);

            if ($this->providerRegistrar->register($projectRoot)) {
                $published[] = 'bootstrap/providers.php';
            }

            foreach ($published as $path) {
                $io->writeln('  <info>+</info> '.$path);
            }

            $io->note([
                'Add before </body> in your layout:',
                "  @include('partials.bladcn-boot')",
                "  @stack('bladcn-scripts')",
                '',
                'Vite (include app.js so bladcn loads):',
                "  @vite(['resources/css/".$options->cssFile."', 'resources/js/app.js'])",
                '',
                'Carousel npm packages (if you use carousel):',
                '  npm install embla-carousel embla-carousel-autoplay',
                '',
                'Recommended dependencies:',
                '  composer require livewire/blaze mallardduck/blade-lucide-icons',
            ]);
        } elseif ($options->publishAssets) {
            $io->warning('Laravel project not detected (artisan). Only bladcn.json was created.');
        }

        $io->writeln([
            '',
            '  Registry: <info>'.$options->registry.'</info>',
            '  Target:   <info>'.$options->componentsPath.'</info>',
            '',
            'Next step:',
            '  <comment>bladcn add button</comment>',
        ]);

        return Command::SUCCESS;
    }

    private function resolveInteractiveOptions(InitOptions $options, SymfonyStyle $io, InputInterface $input): InitOptions
    {
        if ($options->skipPrompts || ! $input->isInteractive()) {
            return $options;
        }

        $withDarkMode = $options->withDarkMode;

        if (! $options->withDarkMode) {
            $withDarkMode = $io->confirm('Include CSS variables for dark mode?', false);
        }

        $cssFile = $options->cssFile;
        if ($options->cssFile === 'app.css') {
            $cssFile = ConsoleInput::nonEmptyAsk($io, 'Main CSS file (relative to resources/css/)', 'app.css');
        }

        $themeFile = $options->themeFile;
        if ($options->themeFile === 'bladcn-theme.css') {
            $themeFile = ConsoleInput::nonEmptyAsk($io, 'CSS theme file', 'bladcn-theme.css');
        }

        return new InitOptions(
            componentsPath: $options->componentsPath,
            registry: $options->registry,
            registryBranch: $options->registryBranch,
            force: $options->force,
            skipPrompts: $options->skipPrompts,
            withDarkMode: $withDarkMode,
            cssFile: $cssFile,
            themeFile: $themeFile,
            publishAssets: $options->publishAssets,
        );
    }

    private function writeConfig(string $projectRoot, InitOptions $options): void
    {
        $data = [
            '$schema' => './vendor/ailuracode/bladcn/resources/bladcn.schema.json',
            'componentsPath' => $options->componentsPath,
            'registry' => BladcnConfig::registryStringForJson($options->registry, $projectRoot),
            'registryBranch' => $options->registryBranch,
            'resolved' => [],
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        throw_if($json === false, RuntimeException::class, 'Could not generate bladcn.json.');

        file_put_contents(BladcnConfig::path($projectRoot), $json.PHP_EOL);
    }

    private function isLaravelProject(string $projectRoot): bool
    {
        return is_file($projectRoot.'/artisan') && is_dir($projectRoot.'/app');
    }
}
