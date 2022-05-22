<?php

namespace DatabaseClass;

use PHPUnit\Framework\TestCase;
use Rocks\Database;

class DatabaseCreateDbTest extends TestCase
{
    public function test__construct()
    {
        $get_table_count = function(bool $clear = false){
            $db = new Database();
            if($clear)
                $db->pdo()->exec('DROP TABLE IF EXISTS `_users`;');
            $stmt = $db->pdo()->prepare('SELECT count(*) AS TOTAL_NUMBER_OF_TABLES FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?;');
            $stmt->execute([$_ENV['DB_DATABASE']]);
            return $stmt->fetch()['TOTAL_NUMBER_OF_TABLES'];
        };

        $before = $get_table_count(true);

        new Database(true);

        $after = $get_table_count();

        $this->assertEquals($after, $before + 1, 'Could not create table in database.');
    }

    public function test__check_db()
    {
        $db = new Database();

        $this->assertFalse($db->check_db(), 'The database check function returned true.');
    }
}
