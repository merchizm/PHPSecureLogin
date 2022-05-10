<?php

namespace Rocks;

use Exception;
use PDO;
use PDOException;

class Database
{
    private PDO $conn;

    function __construct(){
        try{
            $this->conn = new PDO("mysql:host={$_ENV["DB_HOST"]}:{$_ENV['DB_PORT']};dbname={$_ENV["DB_NAME"]};charset=utf8;",
                $_ENV["DB_USERNAME"],
                $_ENV["DB_PASSWORD"]
            );
        } catch (PDOException){
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
        // TODO: Create insert function
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