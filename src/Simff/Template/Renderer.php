<?php

namespace Simff\Template;


use Simff\Main\Simff;

trait Renderer
{
    public static function renderTemplate($template, $params = [])
    {
        return Simff::app()->template->render($template, $params);
    }
}