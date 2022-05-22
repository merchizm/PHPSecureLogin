<?php

namespace Rocks;

use DateTime;
use Exception;
use PDO;
use PDOException;

class Database
{
    /**
     * PDO Instance
     * @var PDO
     */
    private PDO $conn;

    /**
     * Authentication System Required Table Queries
     * @var array|string[]
     */
    private array $table_queries = [
        "create table _users(
    id                  int auto_increment
        primary key,
    username            varchar(25)                                                          null,
    email               varchar(100)                                                         not null,
    password            char(60)                                                             not null,
    name                varchar(64)                                                          null,
    surname             varchar(64)                                                          null,
    birth_date          datetime                                                             null,
    registry_date       datetime default CURRENT_TIMESTAMP                                   null,
    registry_ip_address char(45)                                                             null,
    `2fa_auth_code`     char(128)                                                            not null,
    `2fa_backup_code`   char(6)                                                              null,
    status              enum ('Account Not Verified', 'Account Verified', 'Account Banned.') not null,
    authority           enum ('User', 'Administrator')                                       not null,
    constraint _users_username_uindex
        unique (username)
);",
    ];


    function __construct(private readonly bool $create_db = false){
        try{
            $this->conn = new PDO("mysql:host={$_ENV["DB_HOST"]}:{$_ENV['DB_PORT']};dbname={$_ENV["DB_DATABASE"]};charset=utf8;",
                $_ENV["DB_USERNAME"],
                $_ENV["DB_PASSWORD"]
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // to make it throw an exception.
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // to fetch data faster.
            $this->conn->setAttribute(PDO::ATTR_AUTOCOMMIT, 1); // to make commit automatic.
            if($this->create_db)
                $this->check_db();
        } catch (PDOException){
            return false;
        }
        return true;
    }

    /**
     * A method to get pdo object in case needed
     *
     * @return PDO
     */
    public function pdo(): PDO
    {
        return $this->conn;
    }

    /**
     * Checks total number of tables and if number of tables equal zero, create required tables.
     * @return bool
     */
    public function check_db(): bool
    {
        $stmt = $this->conn->prepare('SELECT count(*) AS TOTAL_NUMBER_OF_TABLES FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?;');
        $stmt->execute([$_ENV['DB_DATABASE']]);
        if($stmt->fetch()['TOTAL_NUMBER_OF_TABLES'] === 0){
            $this->conn->beginTransaction();
            try{
                foreach ($this->table_queries as $table_query) {
                    $this->conn->exec($table_query);
                }
                return true;
            }catch (PDOException){
                $this->conn->rollBack();
                return false;
            }
        }else{
            return false;
        }
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

    /**
     * Create & Run Where query.
     * @param string $table
     * @param string $column
     * @param string $query
     * @param int $limit
     * @return bool|array
     */
    public function where(string $table, string $column, string $query, int $limit = 0): bool|array
    {
        $statement = $this->conn->prepare("SELECT * FROM ? WHERE ? = ? ".($limit > 0) ? " LIMIT $limit;" : ";");
        $statement->execute([$table, $column, $query]);
        return $statement->fetchAll();
    }

    /**
     *
     * @param string $table
     * @param array $tableData
     * @return bool
     */
    public function insert(string $table,array $tableData) : bool {
        $fields = '';
        $values = '';

        foreach ($tableData as $index => $data) {
            $fields .= "$index,";
            if(is_int($data) || is_bool($data) || is_double($data) || is_float($data))
                $values .= "$data,";
            else
                $values .= "'$data',";
        }

        $fields = rtrim($fields, ',');
        $values = rtrim($values, ',');

        return !$this->conn->exec(sprintf('INSERT INTO %s (%s) VALUES(%s)', $table ,$fields, $values)) === false;
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