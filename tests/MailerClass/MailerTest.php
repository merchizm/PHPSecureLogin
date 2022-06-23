<?php

namespace MailerClass;

use PHPUnit\Framework\TestCase;
use Rocks\Mailer;

class MailerTest extends TestCase
{


    public function testSend_verification()
    {
        $mailer = new Mailer();

        $status = $mailer->send_verification($_ENV['TEST_EMAIL'], 'test_url', 'test user');

        $this->assertTrue($status, "Unable to send verification email. Error Message: {$status['message']}");
    }
}
