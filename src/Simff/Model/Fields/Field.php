<?php

namespace Simff\Model\Fields;


abstract class Field
{
    public $null = true;

    public $default = null;

    public $choices = [];

    public $htmlType = 'text';

    public $placeholder = '';

    public $label = '';

    public $required = false;

    public $error = '';

    public $model;

    protected $_value;


    abstract public function getType();

    /**
     * Возвращает строку, содержащую тип поля в бд
     *
     * @return string
     */
    public function getSql()
    {
        $type = $this->getType();
        return $type . $this->getPostfix();
    }
    /**
     * Дефолтные значения для создаваемого поля
     *
     * @return string
     */
    public function getPostfix()
    {
        $postfix = "";
        if (!$this->null) {
            $postfix .= " NOT NULL";
        } else {
            $postfix .= " DEFAULT " . $this->getDefaultSql();
        }
        return $postfix . ", ";
    }
    /**
     * Возвращает псевдослучайное значение для поля
     *
     * @return mixed
     */
    public function getRandomValue()
    {
        return null;
    }
    /**
     * @return null|string
     */
    public function getDefaultSql()
    {
        return is_null($this->default) ? "NULL" : $this->default;
    }

    public function getDefaultSqlValue()
    {
        return is_null($this->default) ? null : $this->default;
    }

    public function getSqlValue()
    {
        if  (!$this->_value) {
            return $this->getDefaultSqlValue();
        }

        return $this->_value;
    }
    /**
     * Возвращает значение поля из бд
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->_value;
    }
    /**
     * @param $value
     */
    public function setValue($value)
    {
        $this->_value = $value;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function getHtmlType()
    {
        return $this->htmlType;
    }

    public function valid()
    {
        if ($this->required && !$this->getValue()) {
            $this->error = 'Обязательно для заполнения';
            return false;
        }

        return true;
    }

    public function delete()
    {

    }
}