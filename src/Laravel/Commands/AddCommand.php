<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Laravel\Commands;

use AiluraCode\Bladcn\Commands\AddCommand as ConsoleAddCommand;
use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\Application;
use Override;
use Symfony\Component\Console\Input\ArrayInput;

final class AddCommand extends Command
{
    #[Override]
    protected $signature = 'bladcn:add
        {components?* : Component names}
        {--all : Install every component from the registry}
        {--overwrite : Overwrite existing components}
        {--no-deps : Skip internal dependencies}
        {--no-external-deps : Skip Composer packages from the registry}
        {--dry-run : Show install plan without copying files}';

    #[Override]
    protected $description = 'Add Blade components and their dependencies';

    public function handle(Application $app): int
    {
        $command = new ConsoleAddCommand;
        $input = new ArrayInput([
            'components' => $this->argument('components'),
            '--all' => $this->option('all'),
            '--overwrite' => $this->option('overwrite'),
            '--no-deps' => $this->option('no-deps'),
            '--no-external-deps' => $this->option('no-external-deps'),
            '--dry-run' => $this->option('dry-run'),
        ]);
        $input->bind($command->getDefinition());

        $previous = getcwd();
        chdir($app->basePath());

        try {
            return $command->run($input, $this->output);
        } finally {
            if ($previous !== false) {
                chdir($previous);
            }
        }
    }
}
