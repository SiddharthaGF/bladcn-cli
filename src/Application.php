<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn;

use AiluraCode\Bladcn\Commands\AddCommand;
use AiluraCode\Bladcn\Commands\InitCommand;
use AiluraCode\Bladcn\Commands\ListCommand;
use AiluraCode\Bladcn\Commands\RemoveCommand;
use AiluraCode\Bladcn\Support\EnvFile;
use Composer\InstalledVersions;
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
        if (class_exists(InstalledVersions::class)) {
            $version = InstalledVersions::getPrettyVersion('ailuracode/bladcn');

            if (is_string($version)) {
                return $version;
            }
        }

        return 'dev';
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
