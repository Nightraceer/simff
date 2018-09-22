<?php

namespace Simff\Main;

use Simff\Helpers\Creator;
use Simff\Helpers\Paths;

trait ConfigModules {

    protected $_modules = [];
    protected $_modulesConfig = [];

    public function setModules($config = [])
    {
        $this->_modulesConfig = $this->prepareModulesConfigs($config);
    }

    public function prepareModulesConfigs($rawConfig)
    {
        $configs = [];
        foreach ($rawConfig as $key => $module) {
            $name = null;
            $config = [];
            if (is_string($module)) {
                $name = $module;
            } elseif (is_string($key)) {
                $name = $key;
                if (is_array($module)) {
                    $config = $module;
                }
            } else {
                throw new \Exception("Unable to configure module {$key}");
            }

            $routesPath = Paths::file("app.Modules.$name", 'php');

            if ($routesPath) {
                $routes = include $routesPath;
                $config['routes'] = $routes;
            }
            $class = '\\Modules\\' . $name . '\\' . $name . 'Module';
            $config['class'] = $class;
            $configs[$name] = $config;
        }
        return $configs;
    }

    public function getModule($name)
    {
        if (!isset($this->_modules[$name])) {
            $config = $this->getModuleConfig($name);
            if (!is_null($config)) {
                $this->_modules[$name] = Creator::run($config);
            } else {
                throw new \Exception("Module with name" . $name . " not found");
            }
        }

        return $this->_modules[$name];
    }

    public function getModuleConfig($name)
    {
        if (array_key_exists($name, $this->_modulesConfig)) {
            return $this->_modulesConfig[$name];
        }
        return null;
    }

    public function getModulesList()
    {
        return array_keys($this->getModulesConfig());
    }

    public function getModulesConfig()
    {
        return $this->_modulesConfig;
    }

    protected function _provideModuleEvent($event, $args = [])
    {
        foreach ($this->getModulesConfig() as $name => $config) {
            $class = $config['class'];
            forward_static_call_array([$class, $event], $args);
        }
    }
}