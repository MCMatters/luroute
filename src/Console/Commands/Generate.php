<?php

declare(strict_types = 1);

namespace McMatters\Luroute\Console\Commands;

use Closure;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Laravel\Lumen\Application;
use Symfony\Component\Console\Input\InputOption;
use const null, DIRECTORY_SEPARATOR;
use function json_encode, rtrim, strtoupper, str_replace;

/**
 * Class Generate
 *
 * @package McMatters\Luroute\Console\Commands
 */
class Generate extends Command
{
    /**
     * @var string
     */
    protected $name = 'luroute:generate';

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var ConfigRepository
     */
    protected $config;

    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * Generate constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        parent::__construct();

        $this->app = $app;
        $this->config = $app->make('config');
        $this->files = $app->make('files');
    }

    /**
     * @return void
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        if ($this->files->put($this->getFilePath(), $this->getCompiledJs())) {
            $this->info('Routes successfully generated.');
        } else {
            $this->error('Can not generate routes. Please check your file permissions');
        }
    }

    /**
     * @return string
     */
    protected function getFilePath(): string
    {
        $path = $this->option('path') ?? $this->config->get('luroute.path');

        $file = $this->option('filename') ?? $this->config->get('luroute.filename');

        return rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file.'.js';
    }

    /**
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function getCompiledJs(): string
    {
        if ($this->option('compress') ?? $this->config->get('luroute.compress')) {
            $template = $this->files->get(__DIR__.'/../stub/luroute.min.js');
        } else {
            $template = $this->files->get(__DIR__.'/../stub/luroute.js');
        }

        $routes = $this->routesToJson($this->getRoutes());
        $namespace = $this->config->get('luroute.namespace');
        $origin = $this->config->get('app.url', '');

        foreach (['routes', 'namespace', 'origin'] as $part) {
            $template = str_replace('DUMMY_'.strtoupper($part), $$part, $template);
        }

        return $template;
    }

    /**
     * @return array
     */
    protected function getRoutes(): array
    {
        $routes = [];

        foreach ($this->app->router->getRoutes() as $route) {
            $action = Arr::get($route, 'action.uses');

            $routes[] = [
                'method' => Arr::get($route, 'method'),
                'uri'    => Arr::get($route, 'uri'),
                'name'   => Arr::get($route, 'action.as'),
                'action' => $action instanceof Closure ? null : $action,
            ];
        }

        return $routes;
    }

    /**
     * @param array $routes
     *
     * @return string
     */
    protected function routesToJson(array $routes): string
    {
        return json_encode($routes);
    }

    /**
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['compress', 'c', InputOption::VALUE_NONE, 'Compress the JavaScript file.', null],
            ['path', 'p', InputOption::VALUE_OPTIONAL, 'Specifying a custom source folder', null],
            ['filename', 'f', InputOption::VALUE_OPTIONAL, 'Specifying a custom file name', null],
        ];
    }
}
