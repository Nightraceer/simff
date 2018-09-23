<?php

namespace Simff\Module;


use ReflectionClass;

class Module
{
    protected static $_paths = [];

    public static function getRoutes()
    {
        return [];
    }

    public static function getPath()
    {
        $class = static::class;

        if (!isset(static::$_paths[$class])) {
            $rc = new ReflectionClass($class);
            static::$_paths[$class] = dirname($rc->getFileName());
        }

        return static::$_paths[$class];
    }
}