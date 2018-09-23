<?php

namespace Simff\Model\Fields;


class CharField extends Field
{
    public $length = 255;

    public function getType()
    {
        return "varchar ($this->length)";
    }
}