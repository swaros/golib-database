<?php

namespace golibdatabase\Database\MySql;

use golib\Types\MapConst;
use golib\Types\Props;
use golibdatabase\Database\Sql\Expression;

/**
 * Description of InsertStatement
 *
 * @author tziegler
 */
class InsertStatement {

    private string $tableName = '';
    private array $fieldNames = array();
    private array $fieldValues = array();
    private bool $duplicateHandling;
    private int $rowCount = 0;

    /**
     * InsertStatement constructor
     * @param string $tableName
     * @param boolean $duplicateHandling use insert on Duplicate Key or not
     */
    public function __construct (string $tableName, $duplicateHandling = true ) {
        $this->tableName = $tableName;
        $this->duplicateHandling = $duplicateHandling;
    }

    /**
     * enables or disables the handling of creating on duplicate key
     *  'extension' for  insertStatements
     * @param mixed $onOffBool
     */
    public function setOnDuplicateKey ( $onOffBool ) {
        $this->duplicateHandling = (bool) $onOffBool;
    }

    public function clear ( $alsoNames = false ) {
        if ($alsoNames === true) {
            $this->fieldNames = array();
        }
        $this->fieldValues = array();
        $this->rowCount = 0;
    }

    /**
     * returns the count of stored row updates
     * @return int
     */
    public function getRowCount () {
        return $this->rowCount;
    }

    /**
     * register a FieldName for insert into this table
     * @param string $name of the Field. have to match witch the real fieldName in the table
     * @param bool $increasedOnUpdate
     * @return InsertStatement
     */
    public function addField (string $name, $increasedOnUpdate = false ) {
        $tableAdd = $this->getRealFieldName( $name );
        $this->fieldNames[$tableAdd] = $increasedOnUpdate;
        return $this;
    }

    /**
     * using props to setup needed fields
     * for insert.
     * all autoincs will be excluded
     * @param Props $props
     * @param array $ignore
     * @param bool $inc
     * @return InsertStatement
     */
    public function addFieldsByProps ( Props $props, array $ignore = array(),
                                       $inc = false ) {
        foreach ($props as $keyName => $value) {
            if ($value !== MapConst::AUTOINC && !in_array( $keyName, $ignore )) {
                $this->addField( $keyName, $inc );
            }
        }
        return $this;
    }

    /**
     * builds a fully qualified fieldname
     * @param string $name
     * @return string
     */
    private function getRealFieldName (string $name) {
        return '`' . $this->tableName . '`.' . str_replace( array(
                    '`',
                    ' ',
                    $this->tableName,
                    '.'), '', $name );
    }

    /**
     * set values for the current Insert Row
     * @param string $fieldName
     * @param mixed $value
     * @return InsertStatement
     */
    public function addValues (string $fieldName, $value) {
        $tableAdd = $this->getRealFieldName( $fieldName );
        $this->fieldValues[$this->rowCount][$tableAdd] = $value;
        return $this;
    }

    /**
     * mark the current row as final
     * @return InsertStatement
     */
    public function rowDone () {
        $this->rowCount++;
        return $this;
    }

    /**
     * get the stored values of a row
     * @param int $rowNumber
     * @param string $fieldName
     * @return Expression|string
     */
    private function getRowContent (int $rowNumber, string $fieldName):Expression|string {
        if (empty($this->fieldValues)) {
            error_log('no values added that can be used together with ' . $fieldName, E_USER_ERROR);
        }
        if (!isset($this->fieldValues[$rowNumber])){
            error_log("unexpected access to index " . $rowNumber . ' field name ' . $fieldName, E_USER_ERROR);
        }
        $data = $this->fieldValues[$rowNumber][$fieldName];
        if ($data instanceof Expression) {
            return $data;
        }
        if ($data === null) {
            return 'null';
        }
        $escaped = $this->mysqlEscape( $data );
        return "'" . $escaped . "'";
    }

    /**
     * mimic mysql_real_escape
     * found: http://php.net/manual/de/function.mysql-real-escape-string.php
     * @param mixed $inp
     * @return string|array;
     */
    private function mysqlEscape ( $inp ) {
        if (is_array( $inp )) {
            return array_map( __METHOD__, $inp );
        }

        if (!empty( $inp ) && is_string( $inp )) {
            return str_replace( array(
                '\\',
                "\0",
                "\n",
                "\r",
                "'",
                '"',
                "\x1a"),
                                array(
                '\\\\',
                '\\0',
                '\\n',
                '\\r',
                "\\'",
                '\\"',
                '\\Z'), $inp );
        }

        return $inp;
    }

    /**
     * adds the duplicate key statement to exsting sql string
     * @param string $sql
     */
    private function addDuplicateStatement (string &$sql) {
        $updateParams = array();
        $sql .= " ON DUPLICATE KEY UPDATE ";
        foreach ($this->fieldNames as $fieldName => $increased) {
            if ($increased === true) {
                $updateParams[] = "{$fieldName} = {$fieldName} + VALUES({$fieldName})";
            } else {
                $updateParams[] = "{$fieldName} = VALUES({$fieldName})";
            }
        }
        $sql .= implode( ',', $updateParams );
    }

    /**
     * builds the insert statement
     * @return string
     */
    public function createInsertStatement () {
        if ($this->rowCount < 1) {
            return '';
        }
        $fieldNames = implode( ',', array_keys( $this->fieldNames ) );
        $sql = "INSERT INTO `{$this->tableName}` ({$fieldNames}) VALUES ";

        $valArray = array();
        for ($i = 0; $i < $this->rowCount; $i++) {
            $strArr = array();
            foreach (array_keys( $this->fieldNames ) as $fieldName) {
                $strArr[] = $this->getRowContent( $i, $fieldName );
            }
            $valArray[] = '(' . implode( ',', $strArr ) . ')';
        }
        $sql .= implode( ',', $valArray );
        if ($this->duplicateHandling) {
            $this->addDuplicateStatement( $sql );
        }

        return $sql;
    }

}
