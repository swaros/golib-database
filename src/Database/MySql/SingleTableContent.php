<?php

namespace golibdatabase\Database\MySql;

use golib\Types\Props;
use golibdatabase\Database\MySql;

/**
 * Description of SingleTableContent
 *
 * @author tziegler
 *
 * class for easy access to one row of a table
 * so this content is mapped into self
 *
 */
abstract class SingleTableContent extends Props implements TableInterface {

    private $_whereSet = NULL;

    /**
     * last used sql
     * @var string
     */
    private $_lastUsedQuery = '';

    /**
     * stored data from last load event
     * @var array
     */
    private $_snapShot = array();

    /**
     * all existing diffs
     * @var array
     */
    private $_diffList = array();
    private $_buildIndex = false;

    /**
     *
     * @param \golibdatabase\Database\MySql\WhereSet $where
     */
    public function __construct ( WhereSet $where = NULL ) {
        $this->_whereSet = $where;
        parent::__construct( NULL );
        $this->init();
    }

    /**
     * get the Data from database
     * by using the submitted db connection
     * @param MySql $db
     * @return type
     */
    public function getData ( MySql $db ) {
        return $this->load( $db );
    }

    /**
     * builds Statement to load content
     * @return string
     */
    private function getLoadStatement () {
        $sql = "SELECT * FROM " . $this->getTableName();
        if ($this->_whereSet != NULL) {
            $sql .= ' WHERE ' . $this->_whereSet->getWhereCondition();
        }
        return $sql;
    }

    /**
     * retunrns the last statement that wasused forloading entries
     * @return string
     */
    public function getLastusedSql () {
        return $this->_lastUsedQuery;
    }

    /**
     * loads data and handle content
     * @param MySql $db
     * @return boolean
     */
    private function load ( MySql $db ) {
        $sql = $this->getLoadStatement();
        $this->_lastUsedQuery = $sql;
        if ($this->_buildIndex) {
            $index = $db->getTableIndex( $this->getTableName() );
            $this->indicies = $index->getPrimary();
        }

        $result = $db->select( $sql );
        if ($result->getError() === NULL) {
            $this->handleResult( $result );
            return true;
        }
        $this->error = $result->getError();
        $this->errorNumber = $result->getErrorNr();
        return false;
    }

    /**
     * gets the current used whereSet
     * @return WhereSet
     */
    public function getCurrentWhereSet () {
        return $this->_whereSet;
    }

    /**
     * handle the data
     * @param \golibdatabase\Database\MySql\ResultSet $result
     */
    private function handleResult ( ResultSet $result ) {
        if ($result->count() != 1) {
            $add = ': got ' . $result->count() . ' rows: [' . $this->_lastUsedQuery . ']';
            throw new TableException( TableException::MESSAGE_NOT_SINGLE . $add,
                                      TableException::CODE_NOT_SINGLE );
        }

        $this->applyData( current( $result->getResult() ) );
        $this->_snapShot = current( $result->getResult() );
    }

    /**
     * updates the Table depending on
     * the changed Values
     * @param MySql $db
     * @return boolean
     */
    public function updateTable ( MySql $db ) {
        $sql = $this->buildDiffUpdateStatement();
        if ($sql !== NULL) {
            $this->_lastUsedQuery = $sql;
            $db->query( $sql );
            $result = ($db->getlastAffectedRows() === 1);
            if ($result == true) {
                $this->updateSnapshotFromDiff();
            }
            return $result;
        }
        return false;
    }

    /**
     * updatesthe snapshot after updateing
     */
    private function updateSnapshotFromDiff () {
        foreach ($this->_diffList as $key => $diff) {
            $this->_snapShot[$key] = (string) $diff['new'];
        }
    }

    /**
     * build a Update statement for
     * changed Props. these Statement includes
     * a check that the update is vlaid only if
     * the old values matching
     * @return string
     */
    private function buildDiffUpdateStatement () {
        $this->findDiffs();
        if (count( $this->_diffList ) < 1) {
            return NULL;
        }
        $sql = "UPDATE " . $this->getTableName() . " SET ";
        $subWhere = new WhereSet();
        $subWhere->applyWhere( $this->getCurrentWhereSet() );
        $set = array();
        foreach ($this->_diffList as $key => $diff) {
            $subWhere->isEqual( $key, $diff['old'] );
            $set[] = "`$key`" . " = '" . $diff['new'] . "'";
        }
        $sql .= implode( ",", $set ) . ' WHERE ' . $subWhere->getWhereCondition();
        return $sql;
    }

    /**
     * calculates the differents between last loaded
     * and changed Props
     */
    public function findDiffs () {
        $this->_diffList = array();
        foreach ($this as $propName => $propValue) {
            $pre = substr( $propName, 0, 1 ); // private variables ignored. that used a single underscore at the beginning
            if ($pre != '_' && isset( $this->_snapShot[$propName] ) && $this->_snapShot[$propName] !== (string) $propValue) {
                $this->diffApply( $propName, $propValue );
            }
        }
    }

    /**
     * apply the value and double check
     * if the diff contains just on different types
     * @param string $propName
     * @param mixed $propValue
     */
    private function diffApply (string $propName, $propValue ) {
        switch (gettype( $propValue )) {
            case 'boolean':
                $propValue = (int) $propValue;
                if ((int) $this->_snapShot[$propName] == $propValue) {
                    return;
                }
                break;
        }
        $this->_diffList[$propName] = array(
            "old"  => $this->_snapShot[$propName],
            "type" => gettype( $propValue ),
            "new"  => $propValue);
    }

    abstract function init ();
}
