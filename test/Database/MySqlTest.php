<?php

namespace Database;

use Exception;
use golibdatabase\Database\MySql;
use golibdatabase\Database\MySql\ConnectInfo;
use mysqli_result;
use PHPUnit\Framework\TestCase;

class MySqlTest extends TestCase
{
    private function checkMysqli() {
        if (!extension_loaded('mysqli')) {
            $this->markTestSkipped(
                'The MySQLi extension is not available.'
            );
        }
    }

    /**
     * @throws Exception
     */
    public function testBase() {
        $this->checkMysqli();

        $result = $this->getMockBuilder(mysqli_result::class)
            ->disableOriginalConstructor()
            ->getMock();

        $conMock = $this->getMockBuilder(MySql\Connect::class)
            ->getMock()
        ;

        $fakeMysqli = new mysqli_fake();
        $fakeMysqli->setQueryResult($result);
        $fakeMysqli->errno = 0;
        $fakeMysqli->affected_rows = 10;
        $fakeMysqli->insert_id = 777;

        $conMock->expects($this->atLeastOnce())
            ->method("getConnection")
            ->willReturn($fakeMysqli);

        $conMock->expects($this->atLeastOnce())
            ->method("isConnected")
            ->willReturn(true);

        $con = new ConnectInfo("user","pw","somewhere","check");
        $mySql = new MySql($con, $conMock);
        $set = $mySql->select('select 1');
        $this->assertNotNull($set);
        $this->assertInstanceOf(MySql\ResultSet::class, $set);
        $this->assertEquals(0, $set->count());
        $this->assertEquals(0, $set->getErrorNr());
        $this->assertEquals(10, $mySql->getlastAffectedRows());
        $this->assertEquals(777, $mySql->getLastInsertId());
    }
}


###### mysqli_fake #####

/**
 * Class mysqli_fake
 * @package Database
 *
 * because mysqli have
 * properties they are not accessible
 * from 'outside', even they are public,
 * it is difficult to get
 * this class mocked... as long
 * there are no methods exists to get
 * results. instead the logic rely
 * on properties
 *
 * props seems not 8.0 fitted
 * but if a declaration is defined, it will
 * be needs to set a initial value too, and will
 * change the origin code too much.
 */
class mysqli_fake {
    /**
     * @var int
     */
    public $affected_rows;
    /**
     * @var string
     */
    public $client_info;
    /**
     * @var int
     */
    public $client_version;
    /**
     * @var int
     */
    public $connect_errno;
    /**
     * @var string
     */
    public $connect_error;
    /**
     * @var int
     */
    public $errno;
    /**
     * @var string
     */
    public $error;
    /**
     * @var int
     */
    public $field_count;
    /**
     * @var string
     */
    public $host_info;
    /**
     * @var string
     */
    public $info;
    /**
     * @var mixed
     */
    public $insert_id;
    /**
     * @var string
     */
    public $server_info;
    /**
     * @var int
     */
    public $server_version;
    /**
     * @var string
     */
    public $sqlstate;
    /**
     * @var string
     */
    public $protocol_version;
    /**
     * @var int
     */
    public $thread_id;
    /**
     * @var int
     */
    public $warning_count;

    /**
     * @var array A list of errors, each as an associative array containing the errno, error, and sqlstate.
     * @link https://secure.php.net/manual/en/mysqli.error-list.php
     */
    public $error_list;


    private $result;
    public function setQueryResult($result) {
        $this->result = $result;
    }
    public function query() {
        return $this->result;
    }
}