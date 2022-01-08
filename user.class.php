<?php

namespace Rocks;

require_once __DIR__."/database.class.php";

use Exception;
use Rocks\Database;

class User
{

    /**
     * @throws RocksException
     */
    function __construct(
        private Database $db
    )
    {
        if(!$this->db->checkConnection())
            throw new RocksException('bağlantılar kurulmamış', code: 3);
    }

    /**
     * @param $username
     * @param $password
     * @return bool
     */
    function auth($username, $password) : bool
    {
        $user = $this->db->where("_users","username", $username);
        if (password_verify($password, $user["password"])) {
            session_regenerate_id();
            $_SESSION['time'] = time();
            $this->user = $user["username"];
            $_SESSION['user'] = $user["username"];
            $_SESSION['auth'] = $user["auth_google"];
            $_SESSION['auth_code'] = $user["auth_code"];
            if($user['status'] == 1):
                return true;
            else:
                return false;
            endif;
        } else {
            return false;
        }
    }

    /**
     * @param array $data
     * @return bool
     * @throws RocksException
     */
    function create(array $data) : bool{
        $d = [
            "username" => $data["username"],
            "authority" => (!isset($data["authority"])) ? "0" : $data["authority"],
            "status" => (!isset($data["status"])) ? "0" : $data["status"],
            "ip_address" => (!isset($data["ip_address"])) ? "" : $data["ip_address"],
            "password" => password_hash($data["pass"], PASSWORD_BCRYPT, array("cost" => 16)),
            "email" => $data["email"],
            "tel_no" => (!isset($data["ip_address"])) ? "" : $data["ip_address"],
            "ref" => (!isset($data["ref"])) ? "" : $data["ref"],
            "reg_time" => iconv('latin5', 'utf-8', strftime('%d %B %Y')),
            "reg_ip_address" => (!isset($data["ip_address"])) ? "" : $data["ip_address"],
            "name_surname" => $data["name_surname"],
            "auth_google" => rand("112121", "999999"),
            "auth_code" => $this->generateRandomSecret()
        ];
        return $this->db->insert("_users", $d);
    }

    /**
     * @throws RocksException
     * @throws Exception
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