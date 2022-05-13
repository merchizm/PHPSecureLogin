<?php

namespace AuthenticatorClass;

use Rocks\Authenticator;
use PHPUnit\Framework\TestCase;

class Get2FACodeTest extends TestCase
{

    private Authenticator|null $auth;
    private string|null $code;

    protected function setUp(): void
    {
        $this->auth = new Authenticator();
        $this->code = $this->auth->get_code($this->auth->generate_random_secret());
    }

    public function test2FaCodeGenerate(){
        $this->assertIsString($this->code, 'Unable to generate 2FA code.');
    }

    /**
     * @depends test2FaCodeGenerate
     */
    public function test2FACodeLength()
    {
        $this->assertEquals(6, strlen($this->code), 'The expected length and actual length are not same.');
    }

    /**
     * @depends test2FaCodeGenerate
     * @depends test2FACodeLength
     */
    public function test2FACodeChange(){
        $secret = $this->auth->generate_random_secret();
        $code = $this->auth->get_code($secret);
        sleep(30);
        $this->assertFalse($code === $this->auth->get_code($secret), 'There was no change on 2FA Code.');
    }

    protected function tearDown(): void
    {
        $this->auth = null;
        $this->code = null;
    }
}
