<?php

namespace DatabaseClass;

use PHPUnit\Framework\TestCase;
use Rocks\Database;

class DatabaseConnectionTest extends TestCase
{

    private Database|null $db;

    protected function setUp(): void
    {
        $this->db = new Database();
    }

    public function test__construct()
    {
        $this->assertTrue($this->db->checkConnection());
    }

    protected function tearDown(): void
    {
        $this->db = null;
    }
}
