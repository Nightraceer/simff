<?php

namespace Simff\Helpers;


trait SmartProperties
{
    public function __get($name)
    {
        return $this->__smartGet($name);
    }

    public function __set($name, $value)
    {
        return $this->__smartSet($name, $value);
    }

    public function __smartGet($name)
    {
        $method = 'get' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$method();
        } else {
            throw new \Exception('Unknown property ' . $name);
        }
    }

    public function __smartSet($name, $value)
    {
        $method = 'set' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$method($value);
        } else {
            throw new \Exception('Unknown property ' . $name);
        }
    }
}