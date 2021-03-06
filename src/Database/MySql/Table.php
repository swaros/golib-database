<?php

namespace golibdatabase\Database\MySql;

use golibdatabase\Database\MySql;
use golibdatabase\Database\MySql\WhereSet;
use golib\Types\PropsFactory;

/**
 * Repesents a Layer for a single Table
 *
 * @author tziegler
 */
abstract class Table implements TableInterface {

    private $tableName = NULL;

    /**
     * first selected item
     * @var PropsFactory
     */
    private $currentItem = NULL;
    private $itemContainer = array();

    /**
     * used wheres set for loading content
     * @var WhereSet
     */
    private $where = NULL;

    /**
     * last mysql error message
     * @var string
     */
    private $error = NULL;

    /**
     * last mysql error code
     * @var int
     */
    private $errorNumber = NULL;
    private $buildIndex = false;

    /**
     * set of indicies
     * @var IndexSet[]
     */
    private $indicies = array();

    /**
     * assigned limit
     * @var Limit
     */
    private $limit = NULL;

    /**
     * assigned sort order
     * @var Order
     */
    private $sort = NULL;

    /**
     * data reading is done
     * @var boolean
     */
    private $fetched = false;

    /**
     * last used sql statement
     *
     * @var string
     */
    public $lastLoadStatement = NULL;

    /**
     * array of fieldnames that are used for select.
     * if empty all fields will be loaded
     * @var type
     */
    private $loadFields = array();

    /**
     *
     * @param WhereSet $where optional if no full content needed
     * @throws \InvalidArgumentException
     */
    public function __construct ( WhereSet $where = NULL, Limit $limit = NULL,
                                  Order $order = NULL ) {
        $tableName = $this->getTableName();
        if (is_string( $tableName ) && $tableName != '') {
            $this->tableName = $tableName;
        } else {
            throw new \InvalidArgumentException( "Tablename must be a String" );
        }
        $this->where = $where;
        $this->limit = $limit;
        $this->sort = $order;
    }

    /**
     * Load Data by using submitted Mysql Connection
     * @param MySql $db
     */
    public function fetchData ( MySql $db ) {
        $this->load( $db );
    }

    /**
     * points to the Limiter
     * @return Limit
     */
    public function getLimit () {
        if ($this->limit == NULL) {
            $this->limit = new Limit();
        }
        return $this->limit;
    }

    /**
     * points to the whereSet
     * @return WhereSet
     */
    public function getWhere () {
        if ($this->where === NULL) {
            $this->where = new WhereSet();
        }
        return $this->where;
    }

    /**
     * points to the order class
     * @return Order
     */
    public function getOrder () {
        if ($this->sort === NULL) {
            $this->sort = new Order();
        }
        return $this->sort;
    }

    /**
     * get the fieldnames for loading (or * if nothing configured)
     *
     * @return string
     */
    private function getLoadFields () {
        if (empty( $this->loadFields )) {
            return '*';
        }
        return implode( ',', $this->loadFields );
    }

    /**
     * creates loaded fieldnames except the fieldnames
     * tey are submitted as array
     * @param array $ignore
     */
    public function setLoadFieldsAndIgnore ( array $ignore = NULL ) {
        $prop = $this->getPropFactory();
        $this->loadFields = array();
        $prefix = $this->getTableName();
        foreach ($prop as $fieldName => $dummy) {
            if ($ignore === NULL || !in_array( $fieldName, $ignore )) {
                $this->loadFields[] = $prefix . '.' . $fieldName;
            }
        }
    }

    /**
     * builds Statement to load content
     * @return string
     */
    private function getLoadStatement () {
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
     * get the amount of entires independend from
     * limitation
     * @param MySql $db
     * @return int
     */
    public function fetchSize ( MySql $db ) {
        $sql = "SELECT count(1) as CNT FROM `{$this->tableName}`";
        if ($this->where != NULL) {
            $sql .= ' WHERE ' . $this->where->getWhereCondition();
        }
        $res = $db->select( $sql );
        if ($res->getErrorNr() === NULL) {
            $d = current( $res->getResult() );
            return (int) $d['CNT'];
        }
        return 0;
    }

    /**
     * loads data and handle content
     * @param MySql $db
     * @return boolean
     */
    private function load ( MySql $db ) {
        $sql = $this->getLoadStatement();
        if ($this->buildIndex) {
            $index = $db->getTableIndex( $this->tableName );
            $this->indicies = $index->getPrimary();
        }

        $result = $db->select( $sql );
        if ($result->getError() === NULL) {
            $this->handleResult( $result );
            $this->fetched = true;
            return true;
        }
        $this->error = $result->getError();
        $this->errorNumber = $result->getErrorNr();
        return false;
    }

    /**
     * get the current select Content
     * @return PropsFactory
     */
    public function getCurrentProp () {
        return $this->currentItem;
    }

    /**
     * return the count of elements
     * @return type
     */
    public function count () {
        if (is_array( $this->itemContainer['content'] )) {
            return count( $this->itemContainer['content'] );
        }
        return 0;
    }

    /**
     * maps current for content
     * @return PropsFactory
     */
    public function current () {
        if (is_array( $this->itemContainer['content'] )) {
            return current( $this->itemContainer['content'] );
        }
    }

    /**
     * maps end for content
     * @return PropsFactory
     */
    public function end () {
        if (is_array( $this->itemContainer['content'] )) {
            return end( $this->itemContainer['content'] );
        }
    }

    /**
     * maps next for Content
     * @return PropsFactory
     */
    public function next () {
        return next( $this->itemContainer['content'] );
    }

    /**
     * maps reset for content
     * @return PropsFactory
     */
    public function reset () {
        if (is_array( $this->itemContainer['content'] )) {
            return reset( $this->itemContainer['content'] );
        }
    }

    /**
     * is true if data was read successfully.
     * on false also check the state of error.
     * any error results also in isFetched == false.
     * @return boolean
     */
    public function isFetched () {
        return $this->fetched;
    }

    /**
     * get the last eror message
     * @return string or NULL
     */
    public function getError () {
        return $this->error;
    }

    /**
     * get entrie by primary key
     * @param type $value
     * @param type $checkNonTypeSave
     * @return PropsPactory
     */
    public function findPrimaryKey ( $value, $checkNonTypeSave = true ) {
        if (!is_array( $this->itemContainer['content'] ) || empty( $this->itemContainer['content'] )) {
            return NULL;
        }
        $this->reset();
        $current = $this->current();
        if ($current == null) {
            return NULL;
        }
        return $this->findFirstMatch( $current->getPrimaryKey(), $value,
                                      $checkNonTypeSave );
    }

    /**
     * find the first matching content
     * @return PropsFactory
     */
    public function findFirstMatch ( $key, $value, $checkNonTypeSave = true ) {


        if (!is_array( $this->itemContainer['content'] ) || empty( $this->itemContainer['content'] )) {
            return NULL;
        }
        foreach ($this->itemContainer['content'] as $content) {
            if ($content->$key === $value) {
                return $content;
            }
            if ($checkNonTypeSave && $content->$key == $value && gettype( $content->$key ) != gettype( $value )) {
                trigger_error( "value matching but Type not matching."
                        . " make sure to store in the expected Format. type for search[{$key}] submited as "
                        . gettype( $value )
                        . ' but stored type is '
                        . gettype( $content->$key ) );
            }
        }
        return NULL;
    }

    /**
     * handle the data
     * @param \golibdatabase\Database\MySql\ResultSet $result
     */
    private function handleResult ( ResultSet $result ) {
        $this->currentItem = NULL;
        $this->itemContainer = array();
        foreach ($result->getResult() as $row) {
            $item = $this->getPropFactory();
            if ($item === NULL || !is_object( $item ) || !($item instanceof PropsFactory)) {
                throw new \Exception( "Make sure " . get_class( $this ) . '::getPropFactory returns a Object of type ResultFactory' );
            }
            $item->applyData( $row );
            if ($this->currentItem == NULL) {
                $this->currentItem = $item;
            }
            $this->itemContainer['content'][] = $this->newItem( $item, true );
            if ($this->buildIndex) {
                $this->indexUpdate( $item );
            }
        }
    }

    public function registerItem ( PropsFactory $item ) {
        $this->itemContainer['content'][] = $this->newItem( $item, false );
    }

    /**
     * updates internal index array
     * @param type $item
     */
    private function indexUpdate ( $item ) {
        $keyValues = array();
        foreach ($this->indicies as $index) {
            $key = $index->Column_name;
            $value = $item->$key;
            $keyValues[] = $value;
        }
        $keyStr = implode( '|', $keyValues );
        $this->itemContainer['index'][$index->Column_name][$keyStr] = $item;
    }

    /**
     * closure executer for any entire
     *
     * @param \Closure $function
     */
    public function foreachCall ( \Closure $function ) {
        $this->reset();
        while ($prop = $this->current()) {
            $function( $prop );
            $this->next();
        }
    }

    /**
     * @return PropsFactory
     */
    abstract function getPropFactory ();

    /**
     * overwrite this for handling
     * new created items
     * @return PropsFactory
     */
    protected function newItem ( PropsFactory $item, $loadedFromDb = true ) {
        return $item;
    }

    /**
     * iterates over all entries and call defined method.
     * this method must define his own PropsFactory as parameter
     * @param string $method name of the methof that must be created in child class
     * @throws \InvalidArgumentException
     */
    public function iterate ( $method ) {
        if ($this->count() < 1) {
            return;
        }
        if (!method_exists( $this, $method )) {
            throw new \InvalidArgumentException( "Method {$method} not defined" );
        }
        $this->reset();
        while ($prop = $this->current()) {
            $this->$method( $prop );
            $this->next();
        }
    }

}
