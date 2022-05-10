<?php

namespace Rocks;

require_once __DIR__ . "/database.class.php";

use Exception;

enum Authority: int
{
    case Admin = 1;
    case User = 0;

    function label(): string
    {
        return match ($this) {
            static::Admin => 'Administrator',
            static::User => 'User'
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
            static::Verified => 'Account Verified',
            static::NotVerified => 'Account Not Verified',
            static::Banned => 'Account Banned',
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
            throw new RocksException('Veritabanı bağlantısı kurulmamış.', code: 3);
    }

    /**
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
            $this->user = $user["username"];
            $_SESSION['user'] = $user["username"];
            $_SESSION['auth'] = $user["auth_google"];
            $_SESSION['auth_code'] = $user["auth_code"];
            if ($user['status'] == 1):
                return true;
            else:
                return false;
            endif;
        } else {
            return false;
        }
    }

    /**
     * Returns end user IP Address
     * @return string
     */
    function getUserIPAddress(): string
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
            "authority" => $authority->value,
            "status" => $status->value,
            "reg_time" => date("Y-m-d H:i:s"),
            "reg_ip_address" => $this->getUserIPAddress(),
            "auth_google" => rand("112121", "999999"),
            "auth_code" => $this->generateRandomSecret()
        ]);
    }

    /**
     * @return string
     * @throws Exception
     * @throws RocksException
     */
    public function generateRandomSecret($secretLength = 16): string
    {
        $secret = '';
        $validChars = array(
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',
            'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
            'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
            'Y', 'Z', '2', '3', '4', '5', '6', '7',
            '=',
        );

        if ($secretLength < 16 || $secretLength > 128)
            throw new RocksException('Bad secret length');

        $random = false;
        if (function_exists('random_bytes')):
            $random = random_bytes($secretLength);
        elseif (function_exists('openssl_random_pseudo_bytes')):
            $random = openssl_random_pseudo_bytes($secretLength, $cryptoStrong);
            if (!$cryptoStrong)
                $random = false;
        endif;
        if ($random !== false) {
            for ($i = 0; $i < $secretLength; ++$i) {
                $secret .= $validChars[ord($random[$i]) & 31];
            }
        } else
            throw new RocksException('Cannot create secure random secret due to source unavailability');

        return $secret;
    }
}