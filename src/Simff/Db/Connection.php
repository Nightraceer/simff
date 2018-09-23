<?php

namespace Simff\Db;


use PDO;

class Connection
{
    /**
     * @var PDO
     */
    protected $_pdo;
    protected $stm = null;

    public $host;
    public $database;
    public $username;
    public $password;
    public $charset = 'utf8';
    public $engine = 'InnoDB';

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

    public function execute($params = [], $reset = false)
    {
        $result = $this->stm->execute($params);

        if ($reset) {
            $this->stm = null;
        }
        return $result;
    }

    public function resultSet($params = [])
    {
        $this->execute($params);
        $result = $this->stm->fetchAll(PDO::FETCH_ASSOC);
        $this->stm = null;
        return $result;
    }

    public function single($params = [])
    {
        $this->execute($params);
        $result = $this->stm->fetch(PDO::FETCH_ASSOC);
        $this->stm = null;
        return $result;
    }
}