<?php

namespace Rocks;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Signer\Key\InMemory;
use DateTimeImmutable;
use Lcobucci\JWT\UnencryptedToken;

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
        return match ($this) {
            AccountStatus::Verified => ['status' => 'success'],
            AccountStatus::NotVerified => ['status' => 'error', 'message' => 'The account has not been verified yet.'],
            AccountStatus::Banned => ['status' => 'error', 'message' => 'The account has been banned.'],
        };
    }
}

class User
{
    private Authenticator $auth;
    private Configuration $jwt_configuration;

    /**
     * @throws RocksException
     */
    function __construct(
        private readonly Database $db,
        private readonly bool $test = false
    )
    {
        $this->auth = new Authenticator();

        $this->jwt_configuration = Configuration::forSymmetricSigner(
        // You may use any HMAC variations (256, 384, and 512)
            new Sha512(),
            InMemory::base64Encoded(base64_encode($_ENV['JWT_256_BIT_KEY']))
        // You may also override the JOSE encoder/decoder if needed by providing extra arguments here
        );
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
    public function auth(string $username, string $password, string $h_captcha_response): bool
    {
        $user = $this->get_user($username);
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
        if($this->test)
            return true;
        $verify = curl_init();
        curl_setopt($verify, CURLOPT_URL, 'https://hcaptcha.com/siteverify');
        curl_setopt($verify, CURLOPT_POST, true);
        curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query([
            'secret' => $_ENV['HCAPTCHA_SECRET_CODE'],
            'response' => $response
        ]));
        curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
        $responseData = json_decode(curl_exec($verify));
        if ($responseData->success)
            return true;
        else
            return false;
    }

    /**
     * Destroy the user session.
     * @return bool
     */
    public function logout(): bool
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
        if ($this->auth->verify_code($_SESSION['temp_code'], $code)) {
            $username = $_SESSION['temp_user'];
            $date_diff = date_diff($_SESSION['time'], date_create());
            $minutes = $date_diff->days * 24 * 60;
            $minutes += $date_diff->h * 60;
            $minutes += $date_diff->i;

            if ($minutes > 5 && session_destroy())
                return json_encode([
                    'status' => 'error',
                    'message' => 'Your session has expired, please try logging in again.',
                    'action' => 'reload_or_href'
                ]);

            if ($this->check_status($username)['status'] !== 'success' && session_destroy())
                return json_encode($this->check_status($username));

            session_regenerate_id(true);
            $_SESSION['user'] = $username;
            $_SESSION['logged_in'] = true;
            $_SESSION['time'] = date_create();
            if ($remember)
                $this->remember();

            return json_encode([
                'status' => 'success',
                'message' => 'You\'re successfully logged in.'
            ]);
        } else
            return json_encode([
                'status' => 'error',
                'message' => 'You entered the wrong two-factor verification code. Please check and try again.'
            ]);
    }

    /**
     * Check Account Status
     * @param $username
     * @return array
     */
    private function check_status($username): array
    {
        $statement = $this->db->where('_users', 'username', $username, null, 'status+0 AS status');
        return AccountStatus::tryFrom($statement['status'])->status();
    }

    /**
     * Remember credentials for 1 week
     */
    private function remember(): void
    {
        $this->db->set_expire('PHPREDIS_SESSION:' . session_id(), (86400 * 7)); // 86400 = 1 day
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
    public function create_user(array $data, Authority $authority, AccountStatus $status): bool
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

    /**
     * Update user.
     * @param array $data
     * @param string $username
     * @return bool
     */
    public function update_user(array $data, string $username): bool
    {
        return $this->db->update("_users", $data, ['username' => $username]);
    }

    /**
     * Delete user.
     * @param string $username
     * @return bool
     */
    public function delete_user(string $username): bool
    {
        return $this->db->remove("_users", ['username' => $username]);
    }

    /**
     * Get user by username
     * @param $username
     * @return bool|array
     */
    public function get_user($username): bool|array
    {
        return $this->db->where('_users', 'username', $username);
    }

    /**
     * Get user by email
     * @param $email
     * @return bool|array
     */
    public function get_user_by_email($email) : bool|array
    {
        return $this->db->where('_users', 'email', $email);
    }

    /**
     * Create JWT token and send verification mail
     * @param string $username
     * @return bool|array
     */
    public function send_verification(string $username): bool|array
    {
        $email = $this->get_user($username)['email'];
        $secret_key = $this->create_jwt($email);
        return Mailer::send_verification($email, $secret_key, $username);
    }

    /**
     * Create JWT Token
     * @param string $email email address to send mail
     * @return string
     */
    private function create_jwt(string $email): string
    {
        $app_url = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] && $_SERVER["HTTPS"] != "off" ? "https" : "http") . "://" . $_SERVER["SERVER_NAME"];
        $now   = new DateTimeImmutable();
        $token = $this->jwt_configuration->builder()
            // Configures the issuer (iss claim)
            ->issuedBy($app_url)
            // Configures the audience (aud claim)
            ->permittedFor($app_url)
            // Configures the time that the token was issue (iat claim)
            ->issuedAt($now)
            // Configures the time that the token can be used (nbf claim)
            ->canOnlyBeUsedAfter($now->modify('+1 minute'))
            // Configures the expiration time of the token (exp claim)
            ->expiresAt($now->modify('+7 days'))
            ->withHeader('email', $email)
            ->getToken($this->jwt_configuration->signer(),  $this->jwt_configuration->signingKey());

        return $token->toString();
    }

    /**
     * Check JWT token
     * @param string $jwt
     * @return bool
     */
    public function check_verification(string $jwt) : bool
    {
        $token = $this->jwt_configuration->parser()->parse($jwt);

        assert($token instanceof UnencryptedToken);

        $constraints = $this->jwt_configuration->validationConstraints();

        if($this->jwt_configuration->validator()->validate($token, ...$constraints))
            return $this->activate_account($token->headers()->get('email'));
        else
            return false;
    }

    /**
     * Activate account without condition
     * @param $email
     * @return bool
     */
    private function activate_account($email) : bool
    {
        return $this->db->update('_users', ['status', 1], ['email', $email]);
    }
}