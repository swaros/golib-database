<?php


namespace Database\Mocks;

use mysqli_result;

/**
 * Class MysqliMock
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
class MysqliMock
{
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

    public array $queries = [];
    public array $queriesAsKey = [];

    private array $queryResults = [];

    /**
     * sets results for queries
     * @param mixed $result
     * @param string $onQuery if not '' then it will be returned if this query is requested
     * @return MysqliMock
     */
    public function setQueryResult($result, string $onQuery = '')
    {
        if ($onQuery != '') {
            $this->queryResults[$onQuery] = $result;
        } else {
            $this->result = $result;
        }
        return $this;
    }



    /**
     * the mysqli query mocked.
     * it returns defined results
     * @param $query
     * @param $mode
     * @return mixed
     */
    public function query($query, $mode)
    {
        $this->queries[] = $query;
        $this->queriesAsKey[$query] = $mode;
        if (array_key_exists($query, $this->queryResults)) {
            return $this->queryResults[$query];
        }
        return $this->result;
    }

    public function __call($name, $arguments)
    {
        error_log("method {$name} called:" . print_r($arguments, true), E_USER_ERROR);
    }
}