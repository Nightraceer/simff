<?php
/**
 * Created by PhpStorm.
 * User: nightracer
 * Date: 22.09.2018
 * Time: 21:52
 */

namespace Simff\Model;


abstract class Model
{
    protected $_fields = [];
    private static $whereClause;
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
    /**
     * Метод, для описания полей модели
     *
     * @return mixed
     */
    abstract public static function getFields();

    /**
     * Создание таблицы
     */
    public function createTable()
    {
        $name = self::getTableName();
        $fields = static::getFields();
        $engine = Db::$engine;
        $charset = Db::$charset;
        $sql = "CREATE TABLE IF NOT EXISTS `$name`(`id` int(11) unsigned NOT NULL AUTO_INCREMENT, ";
        foreach ($fields as $fieldName => $fieldConfig) {
            /** @var Field $field */
            $field = Configurator::create($fieldConfig);
            $sql .= "`$fieldName`" . " " . $field->getSql();
        }
        $sql .= "PRIMARY KEY (`id`)) ENGINE=$engine DEFAULT CHARSET=$charset;";
        Db::getInstance()->query($sql);
        Db::getInstance()->execute();
    }
    /**
     * Получание всех записей данной модели
     *
     * @return array
     */
    public static function all()
    {
        $tableName = self::getTableName();
        Db::getInstance()->query("SELECT * FROM $tableName");
        return self::processQuery();
    }
    private static function processQuery()
    {
        $data = Db::getInstance()->resultSet();
        return self::initModels($data);
    }
    /**
     * Получение записей по условию
     *
     * @return array
     */
    public static function get()
    {
        $tableName = self::getTableName();
        $query = "SELECT * FROM $tableName" . self::$whereClause;
        self::$whereClause = "";
        Db::getInstance()->query($query);
        return self::processQuery();
    }
    /**
     * Создание условия
     *
     * @param $condition
     */
    public static function where($condition)
    {
        self::$whereClause = " WHERE " . $condition;
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
            $model = new static();
            $modelField = static::getFields();
            foreach ($row as $field => $value) {
                if (isset($modelField[$field])) {
                    /** @var Field $initField */
                    $initField = Configurator::create($modelField[$field]);
                    $initField->setValue($value);
                    $model->_fields[$field] = $initField;
                }
            }
            $models[] = $model;
        }
        return $models;
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
            throw new \Exception('Unknown property ' . $name);
        }
    }
}