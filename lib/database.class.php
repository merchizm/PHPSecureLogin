<?php

namespace Rocks;

use DateTime;
use Exception;
use PDO;
use PDOException;
use Redis;

class Database
{
    /**
     * PDO Instance
     * @var PDO
     */
    private PDO $conn;


    /**
     * Redis Instance
     * @var Redis
     */
    private Redis $redis;

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
            // PDO Mysql Connection
            $this->conn = new PDO("mysql:host={$_ENV["DB_HOST"]}:{$_ENV['DB_PORT']};dbname={$_ENV["DB_DATABASE"]};charset=utf8;",
                $_ENV["DB_USERNAME"],
                $_ENV["DB_PASSWORD"]
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // to make it throw an exception.
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // to fetch data faster.
            $this->conn->setAttribute(PDO::ATTR_AUTOCOMMIT, 1); // to make commit automatic.
            if($this->create_db)
                $this->check_db();

            // Redis Connection

            $this->redis = new Redis();
            $this->redis->connect($_ENV['REDIS_HOST'], $_ENV['REDIS_PORT'], $_ENV['REDIS_CONNECT_TIMEOUT'], NULL ,100);

            if(!empty($_ENV['REDIS_PASS'])){
                if(empty($_ENV['REDIS_USERNAME']))
                    $this->redis->auth($_ENV['REDIS_PASS']);
                else
                    $this->redis->auth(['user' => $_ENV['REDIS_USERNAME'], 'pass' => $_ENV['REDIS_PASS']]);
            }else
                return false;

            if($this->redis->ping() !== 'PONG')
                return false;

        } catch (Exception){
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
     * A method to get redis object in case needed
     *
     * @return Redis
     */
    public function redis(): Redis
    {
        return $this->redis;
    }

    /**
     * Set the data in redis string
     * @param $key
     * @param $value
     * @param int|null $expireInSec
     * @return bool|Redis
     */
    public function set_key($key, $value, int $expireInSec = null): bool|Redis
    {
        $set = $this->redis->set($key, $value);
        if($set !== false && $expireInSec !== null)
            $this->redis->expire($key, $expireInSec);
        return $set;
    }

    /**
     * Get the value from redis key
     * @param $key
     * @return false|mixed|Redis|string
     */
    public function get_value($key): mixed
    {
        return $this->redis->get($key);
    }

    /**
     * Find all keys matching the given pattern
     * @param $prefix
     * @return array|Redis
     */
    public function get_keys($prefix = null): array|Redis
    {
        return $this->redis->keys(($prefix === null) ? '*' : $prefix.'*');
    }

    public function set_expire($key, $time): bool|Redis
    {
        return $this->redis->expire($key, $time);
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
     * @param string $selected_columns
     * @return bool|array
     */
    public function where(string $table, string $column, string $query, int $limit = 0, string $selected_columns = '*'): bool|array
    {
        $statement = $this->conn->prepare("SELECT ? FROM ? WHERE ? = ? ".($limit > 0) ? " LIMIT $limit;" : ";");
        $statement->execute([$selected_columns, $table, $column, $query]);
        return $statement->fetch();
    }

    /**
     * Insert data to database
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

    public function update(string $table, array $set,array $where) : bool
    {
        return !$this->conn->exec(sprintf('UPDATE %s SET %s = %s WHERE %s = %s;', $table, $set[0], $set[1], $where[0], $where[1])) === false;
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