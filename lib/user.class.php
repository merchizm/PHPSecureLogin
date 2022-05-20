<?php

namespace Rocks;

require_once __DIR__ . "/database.class.php";

use Exception;
use Rocks\Authenticator;

enum Authority: int
{
    case Admin = 1;
    case User = 0;

    function label(): string
    {
        return match ($this) {
            Authority::Admin => 'Administrator',
            Authority::User => 'User'
        };
    }
}

enum AccountStatus: int
{
    case Verified = 1;
    case NotVerified = 0;
    case Banned = 2;

    function label(): string
    {
        return match ($this) {
            AccountStatus::Verified => 'Account Verified',
            AccountStatus::NotVerified => 'Account Not Verified',
            AccountStatus::Banned => 'Account Banned',
        };
    }
}

class User
{

    /**
     * @throws RocksException
     */
    function __construct(
        private readonly Database $db
    )
    {
        if (!$this->db->checkConnection())
            throw new RocksException('Database connection not established.', code: 3);
    }

    /**
     * Authenticate the user.
     * @param $username
     * @param $password
     * @return bool
     */
    function auth($username, $password): bool
    {
        $user = $this->db->where("_users", "username", $username);
        if (password_verify($password, $user["password"])) {
            session_regenerate_id();
            $_SESSION['time'] = time();
            $_SESSION['temp_user'] = $user["username"];
            $_SESSION['temp_code'] = $user["2fa_auth_code"];
            return true;
        } else {
            return false;
        }
    }

    /**
     * Destroy the user session.
     * @return bool
     */
    function logout() : bool
    {
        return session_destroy();
    }

    function login(){
        $user = $_SESSION['temp_user'];
    }

    function check_auth_code() : bool
    {
        return false;
    }

    /**
     * Returns end user IP Address
     * @return string
     */
    function get_user_ip_address(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else
            $ip = $_SERVER['REMOTE_ADDR'];
        return (string)$ip;
    }

    /**
     * Create a user.
     * @param array $data
     * @param Authority $authority
     * @param AccountStatus $status
     * @return bool
     * @throws RocksException
     */
    function create(array $data, Authority $authority, AccountStatus $status): bool
    {
        return $this->db->insert("_users", [
            ...$data,
            "registry_date" => date("Y-m-d H:i:s"),
            "registry_ip_address" => $this->get_user_ip_address(),
            "2fa_auth_code" => Authenticator::generate_random_secret(),
            "2fa_backup_code" => rand("112121", "999999"),
            "authority" => $authority->value,
            "status" => $status->value,
        ]);
    }
}