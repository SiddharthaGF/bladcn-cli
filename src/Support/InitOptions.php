<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Support;

use AiluraCode\Bladcn\Config\BladcnConfig;
use Symfony\Component\Console\Input\InputInterface;

final readonly class InitOptions
{
    public function __construct(
        public string $componentsPath = 'resources/views/components/ui',
        public string $registry = '',
        public string $registryBranch = 'main',
        public bool $force = false,
        public bool $skipPrompts = false,
        public bool $withDarkMode = false,
        public string $cssFile = 'app.css',
        public string $themeFile = 'bladcn-theme.css',
        public bool $publishAssets = true,
    ) {}

    public static function fromInput(InputInterface $input, string $defaultRegistry): self
    {
        $registry = ConsoleInput::stringOption($input, 'registry', $defaultRegistry);

        return new self(
            componentsPath: ConsoleInput::stringOption($input, 'path', BladcnConfig::defaultComponentsPath()),
            registry: $registry,
            registryBranch: ConsoleInput::stringOption($input, 'branch', BladcnConfig::defaultRegistryBranch()),
            force: ConsoleInput::boolOption($input, 'force'),
            skipPrompts: ConsoleInput::boolOption($input, 'skip-prompts'),
            withDarkMode: ConsoleInput::boolOption($input, 'with-dark-mode'),
            cssFile: ConsoleInput::stringOption($input, 'css-file', BladcnConfig::defaultCssFile()),
            themeFile: ConsoleInput::stringOption($input, 'theme-file', BladcnConfig::defaultThemeFile()),
            publishAssets: ! ConsoleInput::boolOption($input, 'skip-assets'),
        );
    }
}
