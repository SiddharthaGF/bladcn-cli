<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Laravel\Commands;

use AiluraCode\Bladcn\Commands\ListCommand as ConsoleListCommand;
use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\Application;
use Override;
use Symfony\Component\Console\Input\ArrayInput;

final class ListCommand extends Command
{
    #[Override]
    protected $signature = 'bladcn:list';

    #[Override]
    protected $description = 'List components available in the registry';

    public function handle(Application $app): int
    {
        $command = new ConsoleListCommand;
        $input = new ArrayInput([]);
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
