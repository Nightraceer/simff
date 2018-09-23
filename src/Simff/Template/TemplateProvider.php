<?php

namespace Simff\Template;


use Fenom\ProviderInterface;

class TemplateProvider implements ProviderInterface
{
    protected $_paths = [];
    protected $_clear_cache = false;

    public function __construct($template_dirs)
    {
        $this->_paths = $template_dirs;
    }

    public function getSource($tpl, &$time)
    {
        $tpl = $this->_getTemplatePath($tpl);
        if($this->_clear_cache) {
            clearstatcache(true, $tpl);
        }
        $time = filemtime($tpl);
        return file_get_contents($tpl);
    }

    public function getList($extension = "html")
    {
        $list = array();
        foreach ($this->_paths as $path) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path,
                    \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            $path_len = strlen($path);

            foreach ($iterator as $file) {
                /* @var \SplFileInfo $file */
                if ($file->isFile() && $file->getExtension() == $extension) {
                    $list[] = substr($file->getPathname(), $path_len + 1);
                }
            }
        }
        return $list;
    }

    public function getLastModified($tpl)
    {
        $tpl = $this->_getTemplatePath($tpl);

        if($this->_clear_cache) {
            clearstatcache(true, $tpl);
        }
        return filemtime($tpl);
    }

    public function templateExists($tpl)
    {
        foreach ($this->_paths as $path) {
            if (($templatePath = realpath($path . DIRECTORY_SEPARATOR . $tpl)) && strpos($templatePath, $path) === 0) {
                return true;
            }
        }
        return false;
    }

    public function verify(array $templates)
    {

        foreach ($this->_paths as $path) {
            foreach ($templates as $template => $mtime) {
                $template = $path . DIRECTORY_SEPARATOR . $template;

                if(!file_exists($template)){
                    continue;
                }

                if($this->_clear_cache) {
                    clearstatcache(true, $template);
                }

                if (@filemtime($template) !== $mtime) {
                    return false;
                }

            }
        }
        return true;
    }

    protected function _getTemplatePath($tpl)
    {
        foreach ($this->_paths as $path) {
            $templatePath = realpath($path . DIRECTORY_SEPARATOR . $tpl);
            if ($templatePath && strpos($templatePath, $path) === 0) {
                return $templatePath;
            }
        }
        throw new \RuntimeException("Template $tpl not found");
    }
}