<?php

namespace Simff\Model\Fields;


class BooleanField extends Field
{
    public $htmlType = 'checkbox';

    public $length = 1;

    public function getType()
    {
        return "tinyint ({$this->length})";
    }
}