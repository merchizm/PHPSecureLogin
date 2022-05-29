<?php

namespace Rocks;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    static public function send_verification($email, $url, $username): bool|array
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = $_ENV['MAIL_HOST'];                     //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->Username   = $_ENV['MAIL_USERNAME'];                     //SMTP username
            $mail->Password   = $_ENV['MAIL_PASSWORD'];                               //SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
            $mail->Port       = $_ENV['MAIL_PORT'];                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], 'No-Reply');
            $mail->addAddress($email);

            $mail->isHTML();
            $mail->Subject = 'Please confirm your account | '.$_ENV['APP_NAME'];
            $mail->Body    = "Dear newly registered user,\r\nThis is an automatic email sent by the {$_ENV['APP_NAME']} robot.\r\n\r\nYour account will be activated after you confirm it by clicking the link below:\r\n<a href='{$_ENV['VALIDATION_URL']}?token=$url'> {$_ENV['VALIDATION_URL']}?token=$url </a>\r\nAs a reminder, here is your username: $username\r\n";
            $mail->AltBody = "Dear newly registered user,\r\nThis is an automatic email sent by the {$_ENV['APP_NAME']} robot.\r\n\r\nYour account will be activated after you confirm it by clicking the link below:\r\n{$_ENV['VALIDATION_URL']}?token=$url\r\nAs a reminder, here is your username: $username";

            return $mail->send();
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => "Message could not be sent. Mailer Error: $mail->ErrorInfo"];
        }
    }
}