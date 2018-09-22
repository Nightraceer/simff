<?php

namespace Simff\Main;

use Simff\Controller\Controller;
use Simff\Helpers\Creator;
use Simff\Helpers\Paths;
use Simff\Request\HttpRequest;
use Simff\Router\Router;

class Simff
{
    use Components, ConfigModules;

    /**
     * @var self
     */
    protected static $app;

    public static function run($config)
    {
        if (!self::$app) {
            self::$app = Creator::run(self::class, $config);
        }

        self::$app->handleRequest();
        self::$app->end();

    }

    public static function app()
    {
        return self::$app;
    }

    protected function init()
    {
        $this->setUpPaths();
        $this->handleRequest();
    }

    public function setPaths($paths)
    {
        foreach ($paths as $name => $path) {
            Paths::add($name, $path);
        }
    }

    public function setUpPaths()
    {
        $basePath = Paths::get('app');
        if (!is_dir($basePath)) {
            throw new \Exception('App path must be a valid directory. Please, set up correct app path in "paths" section of configuration.');
        }

        $runtimePath = Paths::get('runtime');

        if (!$runtimePath) {
            $runtimePath = Paths::get('app.runtime');
            Paths::add('runtime', $runtimePath);
        }

        if (!is_dir($runtimePath) || !is_writable($runtimePath)) {
            throw new \Exception('Runtime path must be a valid and writable directory. Please, set up correct runtime path in "paths" section of configuration.');
        }

        $modulesPath = Paths::get('Modules');
        if (!$modulesPath) {
            $modulesPath = Paths::get('app.Modules');
            Paths::add('Modules', $modulesPath);
            foreach ($this->getModulesConfig() as $name => $config) {
                $class = $config['class'];
                Paths::add("Modules.{$name}", $class::getPath());
            }
        }
    }

    protected function handleRequest()
    {
        /** @var HttpRequest $request */
        $request = $this->request;
        /** @var Router $router */
        $router = $this->router;
        $url = $request->getUrl();
        $method = $request->getMethod();

        $matches = $router->match($url, $method);
        foreach ($matches as $match) {
            $matched = false;
            if (is_array($match['target']) && isset($match['target'][0])) {
                $controllerClass = $match['target'][0];
                $action = isset($match['target'][1]) ? $match['target'][1] : null;
                $params = $match['params'];

                /** @var Controller $controller */
                $controller = new $controllerClass($this->request);

                $matched = $controller->run($action, $params);

            } elseif (is_callable($match['target'])) {
                $fn = $match['target'];
                $matched = $fn($this->request, $match['params']);
            }
            if ($matched !== false) {
                return true;
            }
        }

        throw new \Exception("Page not found");
    }

    public function end($status = 0, $response = null)
    {
        exit($status);
    }
}