<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn;

use AiluraCode\Bladcn\Commands\AddCommand;
use AiluraCode\Bladcn\Commands\InitCommand;
use AiluraCode\Bladcn\Commands\ListCommand;
use AiluraCode\Bladcn\Commands\RemoveCommand;
use AiluraCode\Bladcn\Support\EnvFile;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\ArgvInput;

final class Application
{
    /** @param list<string> $argv */
    public static function run(array $argv): int
    {
        self::loadEnvironmentFiles();

        $application = new SymfonyApplication('bladcn', self::version());

        $application->addCommands([
            new InitCommand,
            new AddCommand,
            new ListCommand,
            new RemoveCommand,
        ]);

        $application->setDefaultCommand('list');

        return $application->run(new ArgvInput($argv));
    }

    private static function version(): string
    {
        $path = dirname(__DIR__).'/composer.json';

        if (! is_file($path)) {
            return 'dev';
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data)) {
            return 'dev';
        }

        $version = $data['version'] ?? null;

        return is_string($version) ? $version : 'dev';
    }

    private static function loadEnvironmentFiles(): void
    {
        $packageRoot = dirname(__DIR__);
        $cwd = getcwd();

        EnvFile::load($packageRoot.'/.env');

        if ($cwd !== false && $cwd !== $packageRoot) {
            EnvFile::load($cwd.'/.env');
        }
    }
}
