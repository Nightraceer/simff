<?php

namespace Simff\Template;


use Fenom;
use Simff\Helpers\Paths;
use Simff\Helpers\SmartProperties;
use Simff\Main\Simff;

class TemplateManager
{
    use SmartProperties;

    /**
     * @var Fenom
     */
    protected $_renderer;

    public $forceCompile = false;
    public $autoReload = true;
    public $autoEscape = true;

    public $templateFolder = 'views';
    public $cacheFolder = 'views_cache';

    public $librariesCacheTimeout;

    public function init()
    {
        $paths = $this->collectTemplatesPaths();

        $provider = new TemplateProvider($paths);
        $cacheFolder = Paths::get('runtime.' . $this->cacheFolder);
        if (!is_dir($cacheFolder)) {
            mkdir($cacheFolder, 0777, true);
        }
        $this->_renderer = new Fenom($provider);
        $this->_renderer->setCompileDir($cacheFolder);
        $this->_renderer->setOptions([
            'force_compile' => $this->forceCompile,
            'auto_reload' => $this->autoReload,
            'auto_escape' => $this->autoEscape
        ]);
    }

    /**
     * @extension modifier
     * @name info
     * @return Fenom
     */
    public function getRenderer()
    {
        return $this->_renderer;
    }

    /**
     * @return array Paths of templates
     */
    protected function collectTemplatesPaths()
    {
        $activeModules = Simff::app()->getModulesConfig();

        $paths = [];

        foreach ($activeModules as $module => $config) {
            $moduleClass = $config['class'];
            $paths[] = implode(DIRECTORY_SEPARATOR, [$moduleClass::getPath(), $this->templateFolder]);
        }

        return $paths;
    }

    public function render($template, $params = [])
    {
        $result = $this->_renderer->fetch($template, $params);

        return $result;
    }
}