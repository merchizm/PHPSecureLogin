<?php

namespace Rocks;

require_once __DIR__ . "/database.class.php";

use Rocks\Authenticator;

enum Authority: int
{
    case Admin = 2;
    case User = 1;

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
    case NotVerified = 2;
    case Banned = 3;

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
    private \Rocks\Authenticator $auth;

    /**
     * @throws RocksException
     */
    function __construct(
        private readonly Database $db
    )
    {
        $this->auth = new Authenticator();
        if (!$this->db->checkConnection())
            throw new RocksException('Database connection not established.', code: 3);
    }

    /**
     * Authenticate the user and create temporary credentials.
     * @param $username
     * @param $password
     * @return bool
     */
    public function auth($username, $password): bool
    {
        $user = $this->db->where("_users", "username", $username);
        if (password_verify($password, $user["password"])) {
            session_regenerate_id();
            $_SESSION['time'] = date_create();
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
    public function logout() : bool
    {
        return session_destroy();
    }

    /**
     * Check 2FA code and log in.
     * @param $code
     * @param $remember
     * @return bool|string
     */
    public function check_auth_code_and_login($code, $remember): bool|string
    {
        if($this->auth->verify_code($_SESSION['temp_code'], $code)){
            $date_diff = date_diff($_SESSION['time'], date_create());
            $minutes = $date_diff->days * 24 * 60;
            $minutes += $date_diff->h * 60;
            $minutes += $date_diff->i;
            if($minutes < 5){
                $username = $_SESSION['temp_user'];
                session_regenerate_id();
                $_SESSION['user'] = $username;
                $_SESSION['logged_in'] = true;
                $_SESSION['time'] = date_create();
                if($remember)
                    $this->remember($username);
                return json_encode([
                    'status' => 'success',
                    'message' => 'You\'re successfully logged in.'
                ]);
            }else{
                return json_encode([
                    'status' => 'error',
                    'message' => 'Your session has expired, please refresh the page and try again.'
                ]);
            }
        } else {
            return json_encode([
                'status' => 'error',
                'message' => 'Your credentials didn\'t match. Please check your username and password.'
            ]);
        }
    }

    private function remember($username)
    {
        // TODO: make this ;)
    }

    /**
     * Returns end user IP Address
     * @return string
     */
    private function get_ip_address(): string
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
    public function create(array $data, Authority $authority, AccountStatus $status): bool
    {
        return $this->db->insert("_users", [
            ...$data,
            "registry_date" => date("Y-m-d H:i:s"),
            "registry_ip_address" => $this->get_ip_address(),
            "2fa_auth_code" => Authenticator::generate_random_secret(),
            "2fa_backup_code" => rand("112121", "999999"),
            "authority" => $authority->value,
            "status" => $status->value,
        ]);
    }
}