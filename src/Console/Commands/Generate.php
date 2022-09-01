<?php

declare(strict_types=1);

namespace McMatters\Luroute\Console\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Laravel\Lumen\Application;
use Symfony\Component\Console\Input\InputOption;

use function file_get_contents;
use function file_put_contents;
use function is_string;
use function json_encode;
use function rtrim;
use function strtoupper;
use function str_replace;

use const DIRECTORY_SEPARATOR;
use const false;
use const null;
use const true;

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
     * @var \Laravel\Lumen\Application
     */
    protected $app;

    /**
     * @var \Illuminate\Config\Repository
     */
    protected $config;

    /**
     * @var array
     */
    protected $exclude = [];

    /**
     * Generate constructor.
     *
     * @param \Laravel\Lumen\Application $app
     */
    public function __construct(Application $app)
    {
        parent::__construct();

        $this->app = $app;
        $this->config = $app->make('config');

        $this->exclude = $this->config->get('luroute.exclude');
    }

    /**
     * @return int
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle(): int
    {
        if (file_put_contents($this->getFilePath(), $this->getCompiledJs())) {
            $this->info('Routes successfully generated.');

            return self::SUCCESS;
        }

        $this->error('Can not generate routes. Please check your file permissions');

        return self::FAILURE;
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
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function getCompiledJs(): string
    {
        if ($this->option('compress')) {
            $template = file_get_contents(__DIR__.'/../stub/luroute.min.js');
        } else {
            $template = file_get_contents(__DIR__.'/../stub/luroute.js');
        }

        if (!$template) {
            throw new FileNotFoundException();
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
            $uri = $route['uri'] ?? null;
            $name = $route['action']['as'] ?? null;

            if ($this->isRouteExcluded($uri, $name)) {
                continue;
            }

            $action = $route['action']['uses'] ?? null;

            $routes[] = [
                'method' => $route['method'] ?? null,
                'uri' => $uri,
                'name' => $name,
                'action' => $action instanceof Closure ? null : $action,
            ];
        }

        return $routes;
    }

    /**
     * @param string|null $uri
     * @param string|null $name
     *
     * @return bool
     */
    protected function isRouteExcluded(
        ?string $uri = null,
        ?string $name = null
    ): bool {
        foreach (['uri', 'name'] as $item) {
            if (null === $$item) {
                return false;
            }

            foreach ($this->exclude[$item] ?? [] as $exclude) {
                if (!is_string($exclude)) {
                    continue;
                }

                if (Str::endsWith($exclude, '*')) {
                    if (Str::startsWith($$item, Str::substr($exclude, 0, -1))) {
                        return true;
                    }
                } elseif ($exclude === $$item) {
                    return true;
                }
            }
        }

        return false;
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
