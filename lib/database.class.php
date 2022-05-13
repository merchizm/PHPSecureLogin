<?php

namespace Rocks;

use DateTime;
use Exception;
use PDO;
use PDOException;

class Database
{
    private PDO $conn;

    function __construct(){
        try{
            $this->conn = new PDO("mysql:host={$_ENV["DB_HOST"]}:{$_ENV['DB_PORT']};dbname={$_ENV["DB_DATABASE"]};charset=utf8;",
                $_ENV["DB_USERNAME"],
                $_ENV["DB_PASSWORD"]
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // to make it throw an exception.
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // to fetch data faster.
        } catch (PDOException){
            return false;
        }
        return true;
    }

    /**
     * Set the same timezone in PHP and MYSQL.
     * @return bool
     */
    public function setDefaultTimezone(): bool
    {
        $date_time = new DateTime('now');
        return !$this->conn->exec("SET GLOBAL time_zone = '{$date_time->format('P')}';") === false;
    }

    /**
     * Check database connection.
     * @return bool
     */
    public function checkConnection() : bool {
        return (bool) $this->conn;
    }


    public function where(string $table, string $column, string $query, int $limit = 0): bool|array
    {
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