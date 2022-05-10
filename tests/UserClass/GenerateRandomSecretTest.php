<?php

namespace UserClass;

use PHPUnit\Framework\TestCase;
use Rocks\Database;
use Rocks\User;

class GenerateRandomSecretTest extends TestCase
{

    private User|null $user;

    protected function setUp(): void
    {
        $argMock = $this->createMock(Database::class);
        $this->user = new User($argMock);
    }

    public function testGenerateRandomSecret()
    {
        if(function_exists('random_bytes') === false && function_exists('openssl_random_pseudo_bytes') === false)
            $this->fail('random_bytes or openssl_random_pseudo_bytes function doesn\'t exists.');

        $this->assertIsString($this->user->generateRandomSecret());
    }

    /**
     * @depends testGenerateRandomSecret
     */
    public function testGenerateRandomSecretLength()
    {
        $this->assertEquals(16, strlen($this->user->generateRandomSecret()));
    }

    protected function tearDown(): void
    {
        $this->user = null;
    }
}