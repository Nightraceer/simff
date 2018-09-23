<?php

namespace Simff\Model\Fields;


class TextField extends Field
{
    public $htmlType = 'textarea';

    public function getType()
    {
        return "text";
    }
}