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
            if (isset($config['routes'])) {
                if (!isset($config['routes']['methods'])) {
                    $config['routes']['methods'] = ['POST', 'GET'];
                }

                $this->_routes[$name.'.'.$config['routes']['name']] = $config['routes'];
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
            list($methods, $_route, $target, $name, $constraints) = $route;

            $method_match = false;

            foreach ($methods as $enable_method) {
                if (strcasecmp($enable_method, $method) === 0) {
                    $method_match = true;
                    break;
                }
            }

            if (!$method_match) continue;

            $isConstraint = preg_match_all('/{(.*?)}/', $_route, $matchesConstraint);

            if (!$isConstraint) {
                if ($url === $_route) {
                    $matches[] = [
                        'target' => $target,
                        'params' => $params
                    ];
                }
            } else {
                unset($matchesConstraint[0]);

                if ($matchesConstraint) {

                }
            }
        }

        return $matches;
    }
}