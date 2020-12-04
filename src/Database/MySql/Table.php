<?php

namespace golibdatabase\Database\MySql;

use Closure;
use Exception;
use golib\Types\PropsFactory;
use golibdatabase\Database\Message\MessageHandler;
use golibdatabase\Database\Model\ErrorHandler;
use golibdatabase\Database\Model\LoggingEntryPoint;
use golibdatabase\Database\MySql;
use InvalidArgumentException;

/**
 * Class Table
 * @package golibdatabase\Database\MySql
 * @author tziegler <thomas.zglr@googlemail.com>
 */
abstract class Table implements TableInterface, ErrorHandler, LoggingEntryPoint
{
    use MessageHandler;
    private ?string $tableName = NULL;

    /**
     * first selected item
     * @var PropsFactory|null
     */
    private ?PropsFactory $currentItem = NULL;
    private array $itemContainer = array();

    /**
     * used wheres set for loading content
     * @var WhereSet|null
     */
    private ?WhereSet $where = NULL;

    /**
     * last mysql error message
     * @var string|null
     */
    private ?string $error = NULL;

    /**
     * last mysql error code
     * @var int|null
     */
    private ?int $errorNumber = NULL;


    private bool $buildIndex = false;

    /**
     * set of indices
     * @var IndexSet[]
     */
    private array $indices = array();

    /**
     * assigned limit
     * @var Limit|null
     */
    private ?Limit $limit = NULL;

    /**
     * assigned sort order
     * @var Order|null
     */
    private ?Order $sort = NULL;

    /**
     * data reading is done
     * @var boolean
     */
    private bool $fetched = false;

    /**
     * last used sql statement
     *
     * @var string|null
     */
    public ?string $lastLoadStatement = NULL;

    /**
     * array of field names that are used for select.
     * if empty all fields will be loaded
     * @var array
     */
    private array $loadFields = array();


    /**
     *
     * @param WhereSet|null $where optional if no full content needed
     * @param Limit|null $limit
     * @param Order|null $order
     */
    public function __construct(WhereSet $where = NULL, Limit $limit = NULL,
                                Order $order = NULL)
    {
        $tableName = $this->getTableName();
        if (is_string($tableName) && $tableName != '') {
            $this->tableName = $tableName;
        } else {
            throw new InvalidArgumentException("TableName must be a String");
        }
        $this->where = $where;
        $this->limit = $limit;
        $this->sort = $order;
        $this->setPhpNativeTriggerErrorFor(E_USER_ERROR, E_USER_NOTICE, E_USER_WARNING);
    }

    /**
     * @return int|null
     */
    public function getErrorNumber(): ?int
    {
        return $this->errorNumber;
    }

    /**
     * Load Data by using submitted Mysql Connection
     * @param MySql $db
     * @throws Exception
     */
    public function fetchData(MySql $db)
    {
        $this->load($db);
    }

    /**
     * points to the Limiter
     * @return Limit
     */
    public function getLimit()
    {
        if ($this->limit == NULL) {
            $this->limit = new Limit();
        }
        return $this->limit;
    }

    /**
     * points to the whereSet
     * @return WhereSet
     */
    public function getWhere()
    {
        if ($this->where === NULL) {
            $this->where = new WhereSet();
        }
        return $this->where;
    }

    /**
     * points to the order class
     * @return Order
     */
    public function getOrder()
    {
        if ($this->sort === NULL) {
            $this->sort = new Order();
        }
        return $this->sort;
    }

    /**
     * get the field names for loading (or * if nothing configured)
     *
     * @return string
     */
    private function getLoadFields()
    {
        if (empty($this->loadFields)) {
            return '*';
        }
        return implode(',', $this->loadFields);
    }

    /**
     * creates loaded fieldNames except the fieldNames
     * tey are submitted as array
     * @param array|null $ignore
     */
    public function setLoadFieldsAndIgnore(array $ignore = NULL)
    {
        $prop = $this->getPropFactory();
        $this->loadFields = array();
        $prefix = $this->getTableName();
        foreach ($prop as $fieldName => $dummy) {
            if ($ignore === NULL || !in_array($fieldName, $ignore)) {
                $this->loadFields[] = $prefix . '.' . $fieldName;
            }
        }
    }

    /**
     * builds Statement to load content
     * @return string
     */
    private function getLoadStatement()
    {
        $sql = "SELECT " . $this->getLoadFields() . " FROM `{$this->tableName}`";
        if ($this->where != NULL) {
            $sql .= ' WHERE ' . $this->where->getWhereCondition();
        }

        if ($this->sort != NULL) {
            $sql .= ' ' . $this->sort->getSortState();
        }

        if ($this->limit != NULL) {
            $sql .= ' ' . $this->limit->getLimitStr();
        }
        $this->lastLoadStatement = $sql;
        return $sql;
    }

    /**
     * get the amount of entries independent from
     * limitation
     * @param MySql $db
     * @return int
     * @throws Exception
     */
    public function fetchSize(MySql $db)
    {
        $sql = "SELECT count(1) as CNT FROM `{$this->tableName}`";
        if ($this->where != NULL) {
            $sql .= ' WHERE ' . $this->where->getWhereCondition();
        }
        $res = $db->select($sql);
        if ($res->getErrorNr() === NULL) {
            $d = current($res->getResult());
            return (int)$d['CNT'];
        }
        return 0;
    }

    /**
     * loads data and handle content
     * @param MySql $db
     * @return boolean
     * @throws Exception
     */
    private function load(MySql $db): bool
    {
        $sql = $this->getLoadStatement();
        if ($this->buildIndex) {
            $index = $db->getTableIndex($this->tableName);
            $this->indices = $index->getPrimary();
        }

        $result = $db->select($sql);
        if ($result->getError() === NULL) {
            $this->handleResult($result);
            $this->fetched = true;
            return true;
        }
        $this->triggerError(
            "error: " . $result->getError()
            . " errorNr:" . $result->getErrorNr()
            , E_USER_ERROR
        );
        $this->error = $result->getError();
        $this->errorNumber = $result->getErrorNr();
        return false;
    }

    /**
     * get the current select Content
     * @return PropsFactory
     */
    public function getCurrentProp()
    {
        return $this->currentItem;
    }

    /**
     * return the count of elements
     * @return int
     */
    public function count(): int
    {
        if (!$this->contentNotExists()) {
            return count($this->itemContainer['content']);
        }
        return 0;
    }

    /**
     * maps current for content
     * @return PropsFactory|null|bool
     */
    public function current(): PropsFactory|null|bool
    {
        if (!$this->contentNotExists()) {
            return current($this->itemContainer['content']);
        }
        return null;
    }

    /**
     * maps end for content
     * @return PropsFactory|null|bool
     */
    public function end(): PropsFactory|null|bool
    {
        if (!$this->contentNotExists()) {
            return end($this->itemContainer['content']);
        }
        return null;
    }

    /**
     * maps next for Content
     * @return PropsFactory|null|bool
     */
    public function next(): PropsFactory|null|bool
    {
        if (!$this->contentNotExists()) {
            return next($this->itemContainer['content']);
        }
        return false;
    }

    /**
     * maps reset for content
     * @return PropsFactory|null|bool
     */
    public function reset(): PropsFactory|null|bool
    {
        if (!$this->contentNotExists()) {
            return reset($this->itemContainer['content']);
        }
        return false;
    }

    /**
     * is true if data was read successfully.
     * on false also check the state of error.
     * any error results also in isFetched == false.
     * @return boolean
     */
    public function isFetched(): bool
    {
        return $this->fetched;
    }

    /**
     * get the last error message
     * @return string or NULL
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * get entry by primary key
     * @param mixed $value
     * @param bool $checkNonTypeSave
     * @return PropsFactory|null
     */
    public function findPrimaryKey($value, $checkNonTypeSave = true): PropsFactory|null
    {
        if ($this->contentNotExists()) {
            return NULL;
        }
        $this->reset();
        $current = $this->current();
        if ($current == null) {
            return NULL;
        }
        return $this->findFirstMatch($current->getPrimaryKey(), $value, $checkNonTypeSave);
    }

    /**
     * checks if content is existing
     * @param bool $canBeEmpty
     * @return bool
     */
    private function contentNotExists(bool $canBeEmpty = false): bool
    {
        if ($canBeEmpty) {
            return (!array_key_exists('content', $this->itemContainer)
                || !is_array($this->itemContainer['content']));
        }
        return (!array_key_exists('content', $this->itemContainer)
            || !is_array($this->itemContainer['content'])
            || empty($this->itemContainer['content']));
    }

    /**
     * find the first matching content
     * @param $key
     * @param $value
     * @param bool $checkNonTypeSave
     * @return PropsFactory|null
     */
    public function findFirstMatch($key, $value, $checkNonTypeSave = true): PropsFactory|null
    {

        if ($this->contentNotExists()) {
            return NULL;
        }
        foreach ($this->itemContainer['content'] as $content) {
            if ($content->$key === $value) {
                return $content;
            } elseif ($content->$key == $value) {
                if ($checkNonTypeSave && gettype($content->$key) != gettype($value)) {
                    $this->triggerError("value matching but Type not matching."
                        . " make sure to store in the expected Format. type for search[{$key}] submitted as "
                        . gettype($value)
                        . ' but stored type is '
                        . gettype($content->$key), E_USER_WARNING);
                } else {
                    return $content;
                }
            }
        }
        return NULL;
    }

    /**
     * handle the data
     * @param ResultSet $result
     * @throws TableException
     * @throws Exception
     */
    private function handleResult(ResultSet $result)
    {
        $this->currentItem = NULL;
        $this->itemContainer = array();
        foreach ($result->getResult() as $row) {
            $item = $this->getPropFactory();
            if ($item === NULL || !is_object($item) || !($item instanceof PropsFactory)) {
                throw new TableException(
                    "Make sure "
                    . get_class($this)
                    . '::getPropFactory returns a Object of type ResultFactory'
                );
            }
            $item->applyData($row);
            if ($this->currentItem == NULL) {
                $this->currentItem = $item;
            }
            $this->itemContainer['content'][] = $this->newItem($item, true);
            if ($this->buildIndex) {
                $this->indexUpdate($item);
            }
        }
    }

    public function registerItem(PropsFactory $item)
    {
        $this->itemContainer['content'][] = $this->newItem($item, false);
    }

    /**
     * updates internal index array
     * @param mixed $item
     */
    private function indexUpdate($item)
    {
        $keyValues = array();
        foreach ($this->indices as $index) {
            $key = $index->Column_name;
            $value = $item->$key;
            $keyValues[] = $value;
        }
        $keyStr = implode('|', $keyValues);
        if (isset($index)) {
            $this->itemContainer['index'][$index->Column_name][$keyStr] = $item;
        }
    }

    /**
     * closure executer for any entire
     *
     * @param Closure $function
     */
    public function foreachCall(Closure $function)
    {
        $this->reset();
        while ($prop = $this->current()) {
            $function($prop);
            $this->next();
        }
    }

    /**
     * @return PropsFactory
     */
    abstract function getPropFactory();

    /**
     * overwrite this for handling
     * new created items
     * @param PropsFactory $item
     * @param bool $loadedFromDb
     * @return PropsFactory
     */
    protected function newItem(PropsFactory $item, $loadedFromDb = true)
    {
        return $item;
    }

    /**
     * iterates over all entries and call defined method.
     * this method must define his own PropsFactory as parameter
     * @param string $method name of the method that must be created in child class
     * @throws InvalidArgumentException
     */
    public function iterate(string $method)
    {
        if ($this->count() < 1) {
            return;
        }
        if (!method_exists($this, $method)) {
            throw new InvalidArgumentException("Method {$method} not defined");
        }
        $this->reset();
        while ($prop = $this->current()) {
            $this->$method($prop);
            $this->next();
        }
    }

}
