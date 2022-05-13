<?php

namespace AuthenticatorClass;

use Rocks\Authenticator;
use PHPUnit\Framework\TestCase;

class GenerateRandomSecretTest extends TestCase
{

    private Authenticator|null $auth;

    protected function setUp(): void
    {
        $this->auth = new Authenticator();
    }

    public function testGenerateRandomSecret()
    {
        if(function_exists('random_bytes') === false && function_exists('openssl_random_pseudo_bytes') === false)
            $this->fail('random_bytes or openssl_random_pseudo_bytes function doesn\'t exists.');

        $this->assertIsString($this->auth->generate_random_secret());
    }

    /**
     * @depends testGenerateRandomSecret
     */
    public function testGenerateRandomSecretLength()
    {
        $this->assertEquals(128, strlen($this->auth->generate_random_secret()));
    }

    protected function tearDown(): void
    {
        $this->auth = null;
    }
}
