<?php

namespace Simff\Router;


use Simff\Main\Simff;

class Router
{
    protected $_routes = [];

    public function init()
    {
        $modules = Simff::app()->getModulesConfig();
        foreach ($modules as $name => $config) {
            if (method_exists($config['class'], 'getRoutes')) {
                $routes = $config['class']::getRoutes();

                foreach ($routes as $routeConfig) {
                    $this->addRoute($name, $routeConfig);
                }
            }
        }
    }

    public function match($url = null, $method = null)
    {
        $matches = [];
        $params = [];

        if ($url === '') {
            $url = '/';
        }

        if (($strpos = strpos($url, '?')) !== false) {
            $url = substr($url, 0, $strpos);
        }

        foreach ($this->_routes as $route) {
            $methods = $route['methods'];
            $_route = $route['route'];
            $target = $route['target'];
            $constraints = $route['constraints'];
            $name = $route['name'];

            $method_match = false;

            foreach ($methods as $enable_method) {
                if (strcasecmp($enable_method, $method) === 0) {
                    $method_match = true;
                    break;
                }
            }

            if (!$method_match) continue;

            $isConstraint = preg_match_all('/{.*?}/ui', $_route, $matchesConstraint);

            if (!$isConstraint) {
                if ($url === $_route) {
                    $matches[] = [
                        'target' => $target,
                        'params' => $params
                    ];
                }
            } else {
                $pregRoute = $_route;
                $keyParams = [];
                $replace = [];
                foreach (current($matchesConstraint) as $match) {
                    $constraintName = preg_replace('/[{}]/ui', '', $match);
                    $keyParams[] = $constraintName;
                    $params[$match] = '('.$constraints[$constraintName].')';
                }
                $pregRoute = strtr($pregRoute, $params);
                $pregRoute = preg_replace('/\//', '\/', $pregRoute);
                $pregRoute = '/^' . $pregRoute . '$/iu';

                $find = preg_match($pregRoute, $url, $resultMatches);

                if ($find) {
                    unset($resultMatches[0]);
                    $params = array_combine($keyParams, $resultMatches);

                    $matches[] = [
                        'target' => $target,
                        'params' => $params
                    ];
                }
            }
        }

        return $matches;
    }

    protected function addRoute($nameModule, $routeConfig = [])
    {
        $absoluteName = $nameModule . '.' . $routeConfig['name'];

        if (isset($this->_routes[$absoluteName])) {
            throw new \Exception("Can not redeclare route '{$absoluteName}'");
        }

        $methods = isset($routeConfig['methods']) ? $routeConfig['methods'] : ['POST', 'GET'];
        $route = isset($routeConfig['route']) ? $routeConfig['route'] : "/";
        $target = isset($routeConfig['target']) ? $routeConfig['target'] : null;
        $constrains = isset($routeConfig['constraints']) ? $routeConfig['constraints'] : null;
        $name = isset($routeConfig['name']) ? $routeConfig['name'] : "";

        $this->_routes[$absoluteName] = [
            'methods' => $methods,
            'route' => $route,
            'target' => $target,
            'constraints' => $constrains,
            'name' => $name
        ];
    }

    public function getUrl($routeName, $params = [])
    {
        if (isset($this->_routes[$routeName])) {
            $route = $this->_routes[$routeName];
            $url = $route['route'];

            if ($route['constraints']) {
                if (!$params) {
                    throw new \Exception("Route $routeName must accept the parameters");
                }

                $url = preg_replace('/[{}]/iu', '', strtr($url, $params));

                return $url;
            }

            return $url;
        }

        throw new \Exception("Route $routeName not found");
    }
}