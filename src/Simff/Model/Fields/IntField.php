<?php

namespace Simff\Model\Fields;


class IntField extends Field
{
    public $length = 11;

    public function getType()
    {
        return "int ({$this->length})";
    }

    public function getValue()
    {
        $value = parent::getValue();
        if (isset($this->choices[$value])) {
            return $this->choices[$value];
        }
        return $value;
    }
}