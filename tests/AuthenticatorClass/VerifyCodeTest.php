<?php

namespace AuthenticatorClass;

use Rocks\Authenticator;
use PHPUnit\Framework\TestCase;

class VerifyCodeTest extends TestCase
{

    private Authenticator|null $auth;

    protected function setUp(): void
    {
        $this->auth = new Authenticator();
    }

    public function testVerify_code()
    {
        $secret = $this->auth->generate_random_secret();
        $this->assertTrue($this->auth->verify_code($secret, $this->auth->get_code($secret)));
    }
}
