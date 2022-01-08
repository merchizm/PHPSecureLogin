<?php

namespace Rocks;


use Exception;
use PDO;
use Redis;

class Database
{
    private PDO $conn;
    private $temp = null;

    /**
     * Veritabanlarını ayarlamak için host, user, name, pass argümanlarını kullanabilirsiniz.
     * @param array $mysqlConParameters
     * @throws RocksException
     */
    function __construct(
        private array $mysqlConParameters
    ){
        if(count(array_keys($this->mysqlConParameters)) !== 4)
            throw new RocksException("Yeterli parametre yok.", code: 1);

        try{
            $this->conn = new PDO("mysql:host={$this->mysqlConParameters["host"]};dbname={$this->mysqlConParameters["name"]};charset=utf8;",
                (isset($this->mysqlConParameters["user"])) ? $this->mysqlConParameters["user"] : $this->mysqlConParameters["username"],
                (isset($this->mysqlConParameters["pass"])) ? $this->mysqlConParameters["pass"] : $this->mysqlConParameters["password"]);

        } catch (Exception $ex){
            var_dump($ex);
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    public function checkConnection() : bool {
        return (bool) $this->conn;
    }


    public function where(string $table, string $column, string $query, int $limit = 0){
        $statement = $this->conn->prepare("SELECT * FROM ? WHERE ? = ? ".($limit > 0) ? " LIMIT $limit;" : ";");
        $statement->execute([$table, $column, $query]);
        return $statement->fetchAll();
    }

    public function insert(string $table,array $data) : bool {

    }

}

class RocksException extends Exception{

    protected mixed $title;

    public function __construct($message, $title = "Rocks::DB Error", $code = 0, Exception $previous = null) {

        $this->title = $title;

        parent::__construct($message, $code, $previous);

    }

    public function getTitle(){
        return $this->title;
    }

}