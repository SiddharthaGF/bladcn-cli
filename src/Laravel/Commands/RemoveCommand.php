<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Laravel\Commands;

use AiluraCode\Bladcn\Commands\RemoveCommand as ConsoleRemoveCommand;
use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\Application;
use Override;
use Symfony\Component\Console\Input\ArrayInput;

final class RemoveCommand extends Command
{
    #[Override]
    protected $signature = 'bladcn:remove
        {components* : Component names}
        {--no-orphans : Do not remove orphan internal dependencies}
        {--yes : Remove orphan dependencies without prompting}
        {--dry-run : Show removal plan without deleting files}';

    #[Override]
    protected $description = 'Remove components and orphan dependencies';

    public function handle(Application $app): int
    {
        $command = new ConsoleRemoveCommand;
        $input = new ArrayInput([
            'components' => $this->argument('components'),
            '--no-orphans' => $this->option('no-orphans'),
            '--yes' => $this->option('yes'),
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
