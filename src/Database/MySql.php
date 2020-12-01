<?php

namespace golibdatabase\Database;

use Exception;
use golibdatabase\Database\MySql\ConnectInfo;
use golibdatabase\Database\MySql\Index;
use golibdatabase\Database\MySql\ResultSet;
use golibdatabase\Database\Sql\SimpleSql;
use mysqli;
use mysqli_result;

/**
 * Description of MySql
 *
 * @author tziegler
 */
class MySql implements Provider
{

    const TRANS_MODE_NATIVE = 1;
    const TRANS_MODE_MANUEL = 2;
    const TRANS_MODE_UNDEFINED = 0;

    /**
     *
     * @var MySql\Connect|null
     */
    private ?MySql\Connect $connection = NULL;

    /**
     *
     * @var Index|null
     */
    private ?Index $index = NULL;

    /**
     *
     * @var MySql\ConnectInfo|null
     */
    private ?MySql\ConnectInfo $connectionInfo = NULL;

    /**
     * last id number
     * @var int|null
     */
    private ?int $lastInsertId = NULL;

    /**
     * count of fields if re added
     * @var ?int
     */
    private ?int $lastFieldCount = NULL;

    /**
     * last affected rows on updates
     * -1 means nothing to update (becasue of select or something like this)
     * @var ?int
     */
    private ?int $lastAffectedRows = NULL;

    /**
     * if a transaction is started
     * @var boolean
     */
    private bool $transactionStarted = false;

    /**
     * set the mode of transaction. 1 means the native method
     * 2 means manual
     * @var int
     */
    private int $transactionMode = 1;

    /**
     * construct by using connect info
     * @param ConnectInfo $connection
     * @param MySql\Connect|null $connect
     */
    public function __construct(MySql\ConnectInfo $connection, MySql\Connect|null $connect = null)
    {
        $this->connectionInfo = $connection;
        if ($connect == null) {
            $this->connection = new MySql\Connect();
        } else {
            $this->connection = $connect;
        }
    }

    private function connect()
    {
        return $this->connection->connect($this->connectionInfo);
    }

    /**
     *
     * @return MySql\Connect
     */
    public function getDatabaseConnection()
    {
        return $this->connection;
    }

    /**
     * return used mysqli connection
     * @return mysqli connection
     * @throws Exception
     */
    public function getConnection()
    {
        if (!$this->connection->isConnected()) {
            if ($this->connect() === false) {
                throw new Exception("Can't connect to Database:"
                    .
                    $this->connection->getLastError());
            }
        }
        return $this->connection->getConnection();
    }

    /**
     * send query to connected database
     * returns false on error.
     *
     * if you want some results use select instead.
     *
     * @param string $query
     * @param int $resultMode
     * @return bool|mysqli_result
     * @throws Exception
     */
    public function query(string $query, int $resultMode = MYSQLI_STORE_RESULT): bool|mysqli_result
    {
        $this->resetStates();
        $res = $this->getConnection()->query($query, $resultMode);
        if ($this->getConnection()->errno) {
            trigger_error($this->getConnection()->error, E_USER_ERROR);
        }

        $this->lastAffectedRows = $this->getConnection()->affected_rows;
        $this->lastInsertId = $this->getConnection()->insert_id;
        $this->lastFieldCount = $this->getConnection()->field_count;

        return $res;
    }

    /**
     * reset states before reading
     */
    private function resetStates()
    {
        $this->lastAffectedRows = NULL;
        $this->lastInsertId = NULL;
        $this->lastFieldCount = NULL;
    }

    /**
     * returns last affected rows
     * @return int
     */
    public function getlastAffectedRows(): int
    {
        return $this->lastAffectedRows;
    }

    /**
     * returns last insert ID
     * @return int
     */
    public function getLastInsertId(): int
    {
        return $this->lastInsertId;
    }

    /**
     * get the indices from a table
     * @param string $tableName
     * @return Index
     * @throws Exception
     */
    public function getTableIndex(string $tableName): Index
    {

        if ($this->index == NULL) {
            $this->index = new Index();
        }
        $this->index->setCurrentTable($tableName);

        if (!$this->index->existsTableIndex($tableName)) {
            $this->index->registerTableIndex($this);
        }

        return $this->index;
    }

    /**
     * send query and returns result.
     * supports additional params for building query
     * like Mysql::select('SELECT * FROM Table WHERE id = ? AND Username = ?',100,'Manfred');
     * or you can use sprintf compatible placeholder
     * Mysql::select('SELECT * FROM Table WHERE id = %d AND Username = %s',100,'Manfred');
     *
     * use sql expressions for mysql expressions like now
     * use golibdatabase\Database\Sql\Expression;
     * Mysql::select('SELECT * FROM Table WHERE id = %d AND regDate => %s',100,Expression('NOW()'));
     *
     * @param string $query
     * @return ResultSet
     * @throws Exception
     */
    public function select(string $query): ResultSet
    {
        if (func_num_args() > 1) {
            $parameters = array_slice(func_get_args(), 1);
            $qsql = new SimpleSql();
            $query = $qsql->sqlString($query, $parameters);
        }
        $res = $this->query($query, MYSQLI_USE_RESULT);
        $result = new MySql\ResultSet();
        if ($this->getConnection()->errno) {
            $result->setError($this->getConnection()->error);
            $result->setErrorNr($this->getConnection()->errno);
        }
        if ($res instanceof mysqli_result) {
            $result->applyRes($res);

            while ($row = $res->fetch_array(MYSQLI_ASSOC)) {
                $result->applyRow($row);
            }
        }
        return $result;
    }

    /**
     * return true if a Transaction is started and
     * not committed or rolled back.
     * @return boolean
     */
    public function inTransaction(): bool
    {
        return (bool)$this->transactionStarted;
    }

    /**
     * start a Transaction
     * @throws Exception
     */
    public function begin_transaction()
    {
        if ($this->transactionStarted) {
            trigger_error("Transaction already started.use self::inTransaction to determinate if transaction already running");
        }
        $this->transactionStarted = true;

        if (method_exists($this->getConnection(), 'begin_transaction')) {
            $this->getConnection()->begin_transaction();
            $this->transactionMode = self::TRANS_MODE_NATIVE;
        } else {
            $this->transactionMode = self::TRANS_MODE_MANUEL;
            $this->query("SET autocommit=0");
            $this->query("START TRANSACTION");
        }
    }

    /**
     * commit the Transaction
     * @throws Exception
     */
    public function commit()
    {
        if (method_exists($this->getConnection(), 'commit') && $this->transactionMode === self::TRANS_MODE_NATIVE) {
            $this->getConnection()->commit();
        } else {
            $this->query("COMMIT");
            $this->query("SET autocommit=1");
        }
        $this->transactionStarted = false;
    }

    /**
     * rollback a transaction
     * @throws Exception
     */
    public function rollback()
    {
        if (method_exists($this->getConnection(), 'rollback') && $this->transactionMode === self::TRANS_MODE_NATIVE) {
            $this->getConnection()->rollback();
        } else {
            $this->query("ROLLBACK");
            $this->query("SET autocommit=1");
        }
        $this->transactionStarted = false;
    }

    /**
     *
     * @return ConnectData
     */
    public function getConnectionData(): ConnectData
    {
        return $this->connectionInfo;
    }

}
