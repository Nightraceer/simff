<?php

namespace Simff\Helpers;

/**
 * Class Creator
 * @package Simff\Helpers
 */
class Creator
{
    /**
     * @param $class
     * @param array $config
     * @return mixed
     */
    public static function run($class, $config = [])
    {
        list($class, $config) = self::split($class, $config);

        if (isset($config['__construct']) && is_array($config['__construct'])) {
            $obj = new $class(...$config['__construct']);
            unset($config['__construct']);
        } else {
            $obj = new $class;
        }

        $obj = self::configure($obj, $config);
        if (method_exists($obj, 'init')) {
            $obj->init();
        }
        return $obj;
    }

    /**
     * @param $class
     * @param array $config
     * @return array
     * @throws \Exception
     */
    public static function split($class, $config = [])
    {
        if (is_array($class) && isset($class['class'])) {
            $config = $class;
            $class = $config['class'];
            unset($config['class']);
        } elseif (!is_string($class)) {
            throw new \Exception("Class name must be defined");
        }
        return [$class, $config];
    }

    /**
     * @param $object
     * @param $properties
     * @return mixed
     */
    public static function configure($object, $properties)
    {
        foreach ($properties as $name => $value) {
            $object->{$name} = $value;
        }
        return $object;
    }
}