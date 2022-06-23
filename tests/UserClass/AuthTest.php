<?php

namespace UserClass;

use Rocks\AccountStatus;
use Rocks\Authenticator;
use Rocks\Authority;
use Rocks\Database;
use Rocks\RocksException;
use Rocks\User;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{

    /**
     * @throws RocksException
     */
    protected function setUp(): void
    {
        $this->db = new Database();
        $this->userClass = new User($this->db, true);
        $this->authenticatorClass = new Authenticator();
    }

    /**
     * @throws RocksException
     */
    public function testCreate_user()
    {
        $this->assertTrue($this->userClass->create_user([
            'username'=>'test_user',
            'email'=>'test@mail.co',
            'password'=>password_hash('testP@s8W0r%', PASSWORD_BCRYPT),
            'name'=>'test',
            'surname'=>'user',
            'birth_date'=>'2000-12-31 04:39:47'
        ], Authority::User, AccountStatus::Verified), 'User has not been created.');
    }

    /**
     * @requires testCreate_user
     */
    public function testAuth()
    {
        $this->assertTrue($this->userClass->auth('test_user', 'testP@s8W0r%', 'ok'), 'User has not been authenticated.');
    }

    /**
     * @requires testAuth
     */
    public function testLogin()
    {
       $this->assertTrue($this->userClass->check_auth_code_and_login($this->authenticatorClass->get_code($this->userClass->get_user('test_user')['2fa_auth_code']), true), 'User has not been logged in.');
    }
}
