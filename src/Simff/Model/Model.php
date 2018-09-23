<?php

namespace Simff\Model;


use Simff\Db\Connection;
use Simff\Helpers\Creator;
use Simff\Helpers\SmartProperties;
use Simff\Main\Simff;
use Simff\Model\Fields\Field;

abstract class Model
{
    use SmartProperties;

    protected $_fields = [];

    protected $_pk;

    /**
     * Возвращает имя таблицы
     *
     * @return string
     */
    public static function getTableName()
    {
        return self::camelCaseToUnderscores(self::classNameShort());
    }

    public static function classNameShort()
    {
        return substr(static::class, strrpos(static::class, '\\')+1);
    }

    public static function camelCaseToUnderscores($input) {
        return ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $input)), '_');
    }

    public function getPk()
    {
        return $this->_pk;
    }

    public function setPk($value)
    {
        $this->_pk = $value;
    }

    /**
     * Метод, для описания полей модели
     *
     * @return mixed
     */
    abstract public static function getFields();

    /**
     * Создание таблицы
     */
    public static function createTable()
    {
        /** @var Connection $connection */
        $connection = self::connection();
        $name = static::getTableName();
        $fields = static::getFields();
        $engine = $connection->engine;
        $charset = $connection->charset;

        $sql = "CREATE TABLE IF NOT EXISTS `$name`(`id` int(11) unsigned NOT NULL AUTO_INCREMENT, ";
        foreach ($fields as $fieldName => $fieldConfig) {
            /** @var Field $field */
            $field = Creator::run($fieldConfig);
            $sql .= "`$fieldName`" . " " . $field->getSql();
        }
        $sql .= "PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset;";
        $connection->query($sql)->execute([], true);
    }

    /**
     * Конвертация записей из бд в объекты модели
     * и конфигурация полей модели
     *
     * @param $data
     * @return array
     */
    public static function initModels($data)
    {
        $models = [];
        foreach ($data as $row) {

            $models[] = self::initModel($row);
        }
        return $models;
    }

    public static function initModel($row = [])
    {
        $model = new static();
        $modelField = static::getFields();
        foreach ($row as $field => $value) {
            if ($field == 'id') {
                $model->pk = $value;
                continue;
            }

            if (isset($modelField[$field])) {
                /** @var Field $initField */
                $modelField[$field]['model'] = $model;
                $initField = Creator::run($modelField[$field]);
                $initField->setValue($value);
                $model->_fields[$field] = $initField;
            }
        }

        return $model;
    }

    /**
     * Получение значения поля
     *
     * @param $name
     * @return mixed
     * @throws \Exception
     */
    public function __get($name)
    {
        if (isset($this->_fields[$name])) {
            return $this->_fields[$name]->getValue();
        } else {
            $fields = static::getFields();

            if (isset($fields[$name])) {
                $this->_fields[$name] = Creator::run($fields[$name]);
                return $this->_fields[$name]->getValue();
            }

            return $this->__smartGet($name);
        }
    }

    public function __set($name, $value)
    {
        if (isset($this->_fields[$name])) {
            $this->_fields[$name]->setValue($value);
        } else {
            $fields = static::getFields();

            if (isset($fields[$name])) {
                $this->_fields[$name] = Creator::run($fields[$name]);
                $this->_fields[$name]->setValue($value);
            } else {
                $this->__smartSet($name, $value);
            }
        }
    }

    public function getInitFields()
    {
        if (count($this->_fields) != count(static::getFields())) {
            foreach (static::getFields() as $name => $field) {
                if (!isset($this->_fields[$name])) {
                    $field['model'] = $this;
                    $this->_fields[$name] = Creator::run($field);
                }
            }
        }

        return $this->_fields;
    }

    /**
     * @return Connection
     */
    public static function connection()
    {
        return Simff::app()->db;
    }

    public static function all($order = '')
    {
        $sql = "SELECT * FROM " . self::getTableName();

        if ($order) {
            $sql .= "ORDER BY ".$order;
        }

        $data = self::connection()->query($sql)->resultSet();

        return self::initModels($data);
    }

    public static function get($pk)
    {
        $sql = "SELECT * FROM " . static::getTableName() . " WHERE id = :id";

        $data = self::connection()->query($sql)->single(['id' => $pk]);

        return $data ? self::initModel($data) : false;
    }

    public static function getOnAttribute($name, $value)
    {
        $sql = "SELECT * FROM " . self::getTableName() . " WHERE ".$name." = :".$name;

        $data = self::connection()->query($sql)->single([$name => $value]);

        return $data ? self::initModel($data) : false;
    }

    public function save()
    {
        if ($this->pk) {
            return $this->update();
        } else {
            return $this->insert();
        }
    }

    public function update()
    {
        list($values, $query) = $this->prepareFieldsDb();
        $values['id'] = $this->pk;

        $sql = "UPDATE `".self::getTableName()."` SET " . $query . " WHERE id = :id";

        return self::connection()->query($sql)->execute($values, true);
    }

    public function insert()
    {
        list($values, $query) = $this->prepareFieldsDb();

        $sql = "INSERT INTO `".self::getTableName()."` SET " . $query;

        return self::connection()->query($sql)->execute($values, true);
    }

    public function prepareFieldsDb()
    {
        $set = '';
        $values = [];

        /** @var Field $field */
        foreach ($this->getInitFields() as $name => $field) {

            $values[$name] = $this->_fields[$name]->getSqlValue();

            $set.="`".str_replace("`","``", $name) ."`". "=:$name, ";
        }

        return [$values, substr($set, 0, -2)];
    }

    public function delete()
    {
        $sql = "DELETE FROM `". self::getTableName() ."` WHERE id = :id";

        $deleted = self::connection()->query($sql)->execute(['id' => $this->pk], true);

        if ($deleted) {
            foreach ($this->getInitFields() as $field) {
                $field->delete();
            }
        }

        return $deleted;
    }
}