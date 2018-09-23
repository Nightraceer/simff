<?php

namespace Simff\Main;


use Simff\Helpers\Creator;
use Simff\Helpers\SmartProperties;

trait Components
{
    use SmartProperties;

    protected $_components;
    protected $_componentsConfig;

    public function setComponents($config = [])
    {
        $this->_componentsConfig = $config;
    }

    public function getComponent($name)
    {
        if (!isset($this->_components[$name])) {
            if (isset($this->_componentsConfig[$name])) {
                $this->_components[$name] = Creator::run($this->_componentsConfig[$name]);
            } else {
                throw new \Exception("Component with name " . $name . " not found");
            }
        }

        return $this->_components[$name];
    }

    public function setComponent($name, $component)
    {
        if (!is_object($component)) {
            $component = Creator::run($component);
        }
        $this->_components[$name] = $component;
    }

    public function hasComponent($name)
    {
        if (isset($this->_componentsConfig[$name])) {
            return true;
        }
        return false;
    }

    public function __get($name)
    {
        if ($this->hasComponent($name)) {
            return $this->getComponent($name);
        } else {
            return $this->__smartGet($name);
        }
    }
}