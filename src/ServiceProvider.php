<?php

declare(strict_types=1);

namespace McMatters\Luroute;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use McMatters\Luroute\Console\Commands\Generate;

/**
 * Class ServiceProvider
 *
 * @package McMatters\LumenConsoleCommands
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * @return void
     */
    public function boot()
    {
        $this->app->configure('luroute');

        $config = $this->app->make('config');

        if (!$config->has('luroute')) {
            $config->set('luroute', require __DIR__.'/../config/luroute.php');
        }
    }

    /**
     * @return void
     */
    public function register()
    {
        $this->app->singleton('command.luroute.generate', function ($app) {
            return new Generate($app);
        });

        $this->commands(['command.luroute.generate']);
    }
}
