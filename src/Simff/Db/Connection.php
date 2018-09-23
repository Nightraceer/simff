<?php

namespace Simff\Db;


use PDO;

class Connection
{
    /**
     * @var PDO
     */
    protected $_pdo;
    protected $stm;

    public $host;
    public $database;
    public $username;
    public $password;
    public $charset = 'utf8';

    public function init()
    {
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->database . ';charset=' . $this->charset;

        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        );

        $this->_pdo = new PDO($dsn, $this->username, $this->password, $options);
    }

    public function query($sql)
    {
        $this->stm = $this->_pdo->prepare($sql);
        return $this;
    }
    public function execute()
    {
        return $this->stm->execute();
    }
    public function resultSet()
    {
        $this->execute();
        return $this->stm->fetchAll(PDO::FETCH_ASSOC);
    }
    public function single()
    {
        $this->execute();
        return $this->stm->fetch(PDO::FETCH_ASSOC);
    }
    public function rowCount()
    {
        return $this->stm->rowCount();
    }
}