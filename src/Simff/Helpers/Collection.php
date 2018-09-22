<?php

namespace Simff\Helpers;

use Countable;
use Serializable;

class Collection implements Countable, Serializable
{
    protected $_data = [];

    public function __construct($data = [])
    {
        $this->_data = $data;
    }

    public function add($key, $value)
    {
        $this->_data[$key] = $value;
    }

    public function has($key)
    {
        return array_key_exists($key, $this->_data);
    }

    public function get($key, $default = null)
    {
        return $this->has($key) ? $this->_data[$key] : $default;
    }

    public function all()
    {
        return $this->_data;
    }

    public function clear()
    {
        $this->_data = [];
        return $this;
    }

    public function remove($key)
    {
        if ($this->has($key)) {
            unset($this->_data[$key]);
        }
    }

    public function serialize()
    {
        return serialize($this->_data);
    }


    public function unserialize($serialized)
    {
        $this->_data = unserialize($serialized);
    }


    public function count()
    {
        return count($this->_data);
    }
}