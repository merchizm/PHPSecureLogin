<?php

namespace Rocks;

require_once __DIR__ . "/database.class.php";

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

    function status(): array
    {
        return match ($this){
            AccountStatus::Verified => ['status'=> 'success'],
            AccountStatus::NotVerified => ['status'=> 'error', 'message' => 'The account has not been verified yet.'],
            AccountStatus::Banned => ['status'=> 'error', 'message' => 'The account has been banned.'],
        };
    }
}

class User
{
    private Authenticator $auth;

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
     * @param string $username
     * @param string $password
     * @param string $h_captcha_response $_POST['h_captcha_response']
     * @return bool
     */
    public function auth(string $username,string $password, string $h_captcha_response): bool
    {
        $user = $this->db->where('_users', 'username', $username);
        if (password_verify($password, $user['password']) && $this->captcha($h_captcha_response)) {
            $_SESSION['time'] = date_create();
            $_SESSION['temp_user'] = $user['username'];
            $_SESSION['temp_code'] = $user['2fa_auth_code'];
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check captcha
     * @param $response string hCaptcha Response
     * @return bool
     */
    private function captcha(string $response): bool
    {
        $verify = curl_init();
        curl_setopt($verify, CURLOPT_URL, 'https://hcaptcha.com/siteverify');
        curl_setopt($verify, CURLOPT_POST, true);
        curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query([
            'secret' => $_ENV['HCAPTCHA_SECRET_CODE'],
            'response' => $response
        ]));
        curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
        $responseData = json_decode(curl_exec($verify));
        if($responseData->success)
            return true;
        else
            return false;
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
     * @param bool $remember
     * @return bool|string
     */
    public function check_auth_code_and_login($code, bool $remember): bool|string
    {
        if($this->auth->verify_code($_SESSION['temp_code'], $code)){
            $date_diff = date_diff($_SESSION['time'], date_create());
            $minutes = $date_diff->days * 24 * 60;
            $minutes += $date_diff->h * 60;
            $minutes += $date_diff->i;
            if($minutes < 5){
                $username = $_SESSION['temp_user'];
                if($this->check_status($username)['status'] === 'success'){
                    session_regenerate_id(true);
                    $_SESSION['user'] = $username;
                    $_SESSION['logged_in'] = true;
                    $_SESSION['time'] = date_create();
                    if($remember)
                        $this->remember();
                    return json_encode([
                        'status' => 'success',
                        'message' => 'You\'re successfully logged in.'
                    ]);
                }else{
                    session_destroy();
                    return json_encode($this->check_status($username));
                }
            }else{
                session_destroy();
                return json_encode([
                    'status' => 'error',
                    'message' => 'Your session has expired, please try logging in again.',
                    'action' => 'reload_or_href'
                ]);
            }
        } else {
            return json_encode([
                'status' => 'error',
                'message' => 'You entered the wrong two-factor verification code. Please check and try again.'
            ]);
        }
    }

    /**
     * Check Account Status
     * @param $username
     * @return array
     */
    private function check_status($username) : array
    {
        $statement = $this->db->where('_users', 'username', $username, null, 'status+0 AS status');
        return AccountStatus::from($statement['status'])->status();
    }

    /**
     * Remember credentials for 1 week
     */
    private function remember(): void
    {
        $this->db->set_expire('PHPREDIS_SESSION:'.session_id(), (86400 * 7)); // 86400 = 1 day
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
            'registry_date' => date('Y-m-d H:i:s'),
            'registry_ip_address' => $this->get_ip_address(),
            '2fa_auth_code' => Authenticator::generate_random_secret(),
            '2fa_backup_code' => rand(112121, 999999),
            'authority' => $authority->value,
            'status' => $status->value,
        ]);
    }
}