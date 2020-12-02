<?php


namespace Database;

require_once "Mocks/MysqliMock.php";

use Database\Mocks\MysqliMock;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->checkMysqli();
    }


    protected function checkMysqli() {
        if (!extension_loaded('mysqli')) {
            $this->markTestSkipped(
                'The MySQLi extension is not available.'
            );
        }
    }

    protected function getMysqliMock(): MysqliMock {
        return new MysqliMock();
    }

    public function testMock() {
        $this->assertInstanceOf(MysqliMock::class, $this->getMysqliMock());
    }
}