<?php

declare(strict_types=1);

namespace AiluraCode\Bladcn\Laravel;

use AiluraCode\Bladcn\Laravel\Commands\AddCommand as ArtisanAddCommand;
use AiluraCode\Bladcn\Laravel\Commands\InitCommand as ArtisanInitCommand;
use AiluraCode\Bladcn\Laravel\Commands\ListCommand as ArtisanListCommand;
use AiluraCode\Bladcn\Laravel\Commands\RemoveCommand as ArtisanRemoveCommand;
use Illuminate\Support\ServiceProvider;
use Override;

final class BladcnCliServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 2).'/config/bladcn.php', 'bladcn');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                dirname(__DIR__, 2).'/config/bladcn.php' => config_path('bladcn.php'),
            ], 'bladcn-config');

            $this->commands([
                ArtisanInitCommand::class,
                ArtisanAddCommand::class,
                ArtisanListCommand::class,
                ArtisanRemoveCommand::class,
            ]);
        }
    }
}
