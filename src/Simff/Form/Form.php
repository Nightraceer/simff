<?php

namespace Simff\Form;


use Simff\Model\Model;

class Form
{
    protected $_errors = [];

    /** @var  Model */
    protected $model;

    /** @var  Model */
    public $modelClass;
    public $exclude = [];

    public function __construct($modelClass)
    {
        $this->modelClass = $modelClass;
    }

    public function fill($data = [], $files = [])
    {
        $formName = $this->getName();
        $data = $data[$formName];
        $filled = false;

        foreach ($data as $name => $value) {
            if (isset($this->getFields()[$name])) {
                $this->getFields()[$name]->setValue($value);

                if (!$this->getFields()[$name]->valid()) {
                    $this->addError($name, $this->getFields()[$name]->error);
                }
            }

            $filled = true;
        }

        if ($this->hasFiles($files)) {
            $preparedFiles = $this->prepareFiles($files[$formName]);

            foreach ($preparedFiles as $name => $value) {
                $this->getFields()[$name]->prepare($value);

                if (!$this->getFields()[$name]->valid()) {
                    $this->addError($name, $this->getFields()[$name]->error);
                }
            }

            $filled = true;
        }

        return $filled;
    }

    public function hasFiles($files)
    {
        $has = true;

        if (empty($files)) {
            $has = false;
        }

        if ($has && !isset($files[$this->getName()])) {
            $has = false;
        }

        if($has){
            $filesData = $files[$this->getName()];

            if (isset($filesData['error'])) {
                $has = false;
                $errors = $filesData['error'];
                foreach ($errors as $error) {
                    if ($error != UPLOAD_ERR_NO_FILE) {
                        $has = true;
                        break;
                    }
                }
            }
        }

        return $has;
    }

    public function prepareFiles($_files = [])
    {
        $files = [];
        foreach (array_keys($_files) as $keyProp => $prop) {
            $propValue = $_files[$prop];
            foreach ($propValue as $key => $value) {
                $value = (!is_array($value)) ? (array)$value : $value;
                foreach ($value as $keyValue => $val) {
                    $files[$key][$prop] = $val;
                }
            }
        }
        return $files;
    }

    public function validate()
    {
        if ($this->getErrors()) {
            return false;
        }

        return true;
    }

    public function addError($field, $error)
    {
        $this->_errors[$field] = $error;
    }

    public function getErrors()
    {
        return $this->_errors;
    }

    public function getName()
    {
        return $this->modelClass::classNameShort();
    }

    public function getFields()
    {
        return $this->getModel()->getInitFields();
    }

    public function getField($name)
    {
        return isset($this->getFields()[$name]) ? $this->getFields()[$name] : null;
    }

    public function getModel()
    {
        if (!$this->model) {
            $this->model = new $this->modelClass;
        }

        return $this->model;
    }

    public function save()
    {
        return $this->getModel()->save();
    }
}