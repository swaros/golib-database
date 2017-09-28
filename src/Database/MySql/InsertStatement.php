<?php

namespace golibdatabase\Database\MySql;

use golibdatabase\Database\Sql\Expression;
use golib\Types\Props;
use golib\Types\MapConst;

/**
 * Description of InsertStatement
 *
 * @author tziegler
 */
class InsertStatement {

    private $tableName = '';
    private $fieldNames = array();
    private $fieldValues = array();
    private $duplicateHandling = true;
    private $rowCount = 0;

    /**
     *
     * @param string $tableName
     * @param boolean $duplicateHandling use insert on Dublicate Key or not
     */
    public function __construct ( $tableName, $duplicateHandling = true ) {
        $this->tableName = $tableName;
        $this->duplicateHandling = $duplicateHandling;
    }

    /**
     * enables or disbales the handling of creating on duplicate key
     *  'extension' for  insertstatements
     * @param type $onOffBool
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
     * register a Fieldname for insert into this table
     * @param string $name of the Field. have to match witch the real fieldname in the table
     * @param type $increasedOnUpdate
     * @return \golibdatabase\Database\MySql\InsertStatement
     */
    public function addField ( $name, $increasedOnUpdate = false ) {
        $tableAdd = $this->getRealFieldName( $name );
        $this->fieldNames[$tableAdd] = $increasedOnUpdate;
        return $this;
    }

    /**
     * using props to setup needed fields
     * for insert.
     * all autoincs will be excluded
     * @param Props $props
     * @return \golibdatabase\Database\MySql\InsertStatement
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
    private function getRealFieldName ( $name ) {
        return $this->tableName . '.' . str_replace( array(
                    '`',
                    ' ',
                    $this->tableName,
                    '.'), '', $name );
    }

    /**
     * set values for the current Insert Row
     * @param string $fieldname
     * @param mixed $value
     * @return \golibdatabase\Database\MySql\InsertStatement
     */
    public function addValues ( $fieldname, $value ) {
        $tableAdd = $this->getRealFieldName( $fieldname );
        $this->fieldValues[$this->rowCount][$tableAdd] = $value;
        return $this;
    }

    /**
     * mark the current row as final
     * @return \golibdatabase\Database\MySql\InsertStatement
     */
    public function rowDone () {
        $this->rowCount++;
        return $this;
    }

    /**
     * get the stored values of a row
     * @param int $rowNumber
     * @param string $fieldname
     * @return Expression
     */
    private function getRowContent ( $rowNumber, $fieldname ) {
        $data = $this->fieldValues[$rowNumber][$fieldname];
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
     * @param type $inp
     * @return type
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
    private function addDublicateStatement ( &$sql ) {
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
        $sql = "INSERT INTO {$this->tableName} ({$fieldNames}) VALUES ";

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
            $this->addDublicateStatement( $sql );
        }

        return $sql;
    }

}
