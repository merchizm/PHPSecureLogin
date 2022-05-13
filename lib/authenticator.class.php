<?php

namespace Rocks;

use Exception;

class Authenticator
{

    protected int $length = 6;
    private array $valid_chars = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',
        'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
        'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
        'Y', 'Z', '2', '3', '4', '5', '6', '7',
        '=',
    ];


    /**
     * Generates a random secret key of specified length.
     * @param int $secretLength
     * @return string secret key
     * @throws RocksException
     * @throws Exception
     */
    public function generate_random_secret(int $secretLength = 128): string
    {
        $secret = '';
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
                $secret .= $this->valid_chars[ord($random[$i]) & 31];
            }
        } else
            throw new RocksException('Cannot create secure random secret due to source unavailability');

        return $secret;
    }

    /**
     * Fetch 2FA code.
     * @param string $secret secret key
     * @param int|null $time_slice Valid period
     * @return string 2FA code
     */
    public function get_code(string $secret, int $time_slice = null): string
    {
        if ($time_slice === null)
            $time_slice = floor(time() / 30);

        $secret_key = $this->debase32($secret);

        $time = chr(0).chr(0).chr(0).chr(0).pack('N*', $time_slice);
        $hm = hash_hmac('SHA1', $time, $secret_key, true);
        $offset = ord(substr($hm, -1)) & 0x0F;
        $hash_part = substr($hm, $offset, 4);

        $value = unpack('N', $hash_part);
        $value = $value[1];
        $value = $value & 0x7FFFFFFF;

        $modulo = pow(10, $this->length);

        return str_pad($value % $modulo, $this->length, '0', STR_PAD_LEFT);
    }


    public function get_qr($name, $secret, $title = null, $params = array()): string
    {
        $width = !empty($params['width']) && (int) $params['width'] > 0 ? (int) $params['width'] : 200;
        $height = !empty($params['height']) && (int) $params['height'] > 0 ? (int) $params['height'] : 200;
        $level = !empty($params['level']) && in_array($params['level'], array('L', 'M', 'Q', 'H')) ? $params['level'] : 'M';

        $urlencoded = urlencode('otpauth://totp/'.$name.'?secret='.$secret);
        if (isset($title))
            $urlencoded .= urlencode('&issuer='.urlencode($title));

        return 'https://chart.googleapis.com/chart?chs='.$width.'x'.$height.'&chld='.$level.'|0&cht=qr&chl='.$urlencoded;
    }

    public function verify_code($secret, $code, $discrepancy = 1, $currentTimeSlice = null): bool
    {
        if ($currentTimeSlice === null) {
            $currentTimeSlice = floor(time() / 30);
        }

        if (strlen($code) !== 6) {
            return false;
        }

        for ($i = -$discrepancy; $i <= $discrepancy; ++$i) {
            $calculatedCode = $this->get_code($secret, $currentTimeSlice + $i);
            if ($this->timing_safe_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sets the 2FA Code length.
     * @param int $length length of 2FA Code to be generated
     * @return $this
     */
    public function set_code_length(int $length): static
    {
        $this->length = $length;
        return $this;
    }


    protected function debase32($secret): bool|string
    {
        if (empty($secret)) {
            return '';
        }
        
        $base32charsFlipped = array_flip($this->valid_chars);

        $paddingCharCount = substr_count($secret, $this->valid_chars[32]);
        $allowedValues = array(6, 4, 3, 1, 0);
        if (!in_array($paddingCharCount, $allowedValues)) {
            return false;
        }
        for ($i = 0; $i < 4; ++$i) {
            if ($paddingCharCount == $allowedValues[$i] &&
                substr($secret, -($allowedValues[$i])) != str_repeat($this->valid_chars[32], $allowedValues[$i])) {
                return false;
            }
        }
        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = '';
        for ($i = 0; $i < count($secret); $i = $i + 8) {
            $x = '';
            if (!in_array($secret[$i], $this->valid_chars)) {
                return false;
            }
            for ($j = 0; $j < 8; ++$j) {
                $x .= str_pad(base_convert(@$base32charsFlipped[@$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
            }
            $eightBits = str_split($x, 8);
            for ($z = 0; $z < count($eightBits); ++$z) {
                $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : '';
            }
        }

        return $binaryString;
    }


    private function timing_safe_equals($safeString, $userString): bool
    {
        if (function_exists('hash_equals')) {
            return hash_equals($safeString, $userString);
        }
        $safeLen = strlen($safeString);
        $userLen = strlen($userString);

        if ($userLen != $safeLen) {
            return false;
        }

        $result = 0;

        for ($i = 0; $i < $userLen; ++$i) {
            $result |= (ord($safeString[$i]) ^ ord($userString[$i]));
        }
        return $result === 0;
    }
}