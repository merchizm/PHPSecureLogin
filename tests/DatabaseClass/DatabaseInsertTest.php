<?php

namespace DatabaseClass;

use PHPUnit\Framework\TestCase;
use Rocks\Authenticator;
use Rocks\Database;

class DatabaseInsertTest extends TestCase
{

    private Database|null $db;

    protected function setUp(): void
    {
        $this->db = new Database();
    }

    public function testInsert()
    {
        $sample_data = [
            ["username"=>"test_user", "email"=>'test@mail.co', "password"=>password_hash('test', PASSWORD_BCRYPT), "name"=>'test', "surname"=>'user', "birth_date"=>'2000-12-31 04:39:47',"registry_date"=> '2020-12-31 14:58:47', "registry_ip_address"=>'127.0.0.1',"2fa_auth_code" => Authenticator::generate_random_secret(), "2fa_backup_code"=>rand(111111,999999) , "status"=>1, "authority"=>1],
            ["username"=>"test2_user", "email"=>'tes2t@mail.co', "password"=>password_hash('test', PASSWORD_BCRYPT), "name"=>'test2', "surname"=>'user2', "birth_date"=>'1994-05-22 19:58:47',"registry_date"=> '2021-02-11 16:21:11', "registry_ip_address"=>'127.0.1.1',"2fa_auth_code" => Authenticator::generate_random_secret(), "2fa_backup_code"=>rand(111111,999999) , "status"=>2, "authority"=>1],
        ];

        $r = [];
        foreach ($sample_data as $index => $data) {
            $r[$index] =  $this->db->insert('_users', $data);
        }

        foreach ($r as $item) {
            $this->assertTrue($item, 'No records have been entered in the database.');
        }
    }

    protected function tearDown(): void
    {
        $this->db->pdo()->exec("TRUNCATE TABLE _users;");
        $this->db = null;
    }
}
