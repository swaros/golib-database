<?php

namespace golibdatabase\Database\MySql;

use golib\Types\Props;
use golibdatabase\Database\Sql\SimpleSql;

/**
 * Description of UpdateStatement
 *
 * @author tziegler
 */
class UpdateStatement {

    /**
     *
     * @var Table
     */
    private $table = NULL;

    /**
     *
     * @var Diff\Row[]
     */
    private $diffList = array();
    private $ignoreList = array();
    private $useWhereFromTable = false;
    private $ignoreWhereOnUpdate = array();
    private $lastAffectedProps = array();

    public function __construct ( Table $table, $useTableWhere = false ) {
        $this->table = $table;
        $this->useWhereFromTable = $useTableWhere;
    }

    public function clearDiff () {
        $this->diffList = array();
    }

    public function setFieldsIgnoredOnWhere ( array $list ) {
        $this->ignoreWhereOnUpdate = $list;
    }

    public function setIgnoreList ( array $list ) {
        $this->ignoreList = $list;
    }

    /**
     * compare two props and build
     * diff for update
     * @param Props $origin the Props that contains the old values
     * @param Props $updated the Props that was updated
     */
    public function updateDiff ( Props $origin, Props $updated ) {
        $id = uniqid();

        foreach ($origin as $prop => $oldValue) {
            if (!in_array( $prop, $this->ignoreList ) && $oldValue != $updated->$prop) {
                $this->registerUpdate( $id, $prop, $updated->$prop, $origin );
            }
        }
    }

    /**
     * register a update by fieldname
     * and value with refrence to to origin Propertie
     * @param type $fieldname
     * @param type $value
     * @param type $oldValue
     */
    private function registerUpdate ( $id, $fieldname, $value, Props $origin ) {
        $diff = new Diff\Row();
        $diff->name = $fieldname;
        $diff->value = $value;
        $diff->originSet = $origin;
        $this->diffList[$id][] = $diff;
    }

    /**
     * compose update querys by difflist
     * @return array
     */
    public function getUpdates () {
        $allUpdates = array();
        $this->lastAffectedProps = array();
        foreach ($this->diffList as $uid => $row) {

            $subWhere = new WhereSet();
            $updates = array();
            $sql = "UPDATE "
                    . $this->table->getTableName()
                    . ' SET ';
            foreach ($row as $fields) {
                $updates[] = $this->getStatement( $fields );
            }
            $this->lastAffectedProps[$uid] = $fields->originSet;
            $this->applyWhereByProps( $fields->originSet, $subWhere );

            $sql .= implode( ',', $updates );
            $allUpdates[$uid] = $this->composeWhere( $sql, $subWhere );
        }
        return $allUpdates;
    }

    /**
     * get the last handled Props
     * @return Diff\Row[]
     */
    public function getLastUpdated () {
        return $this->lastAffectedProps;
    }

    /**
     * compose the where statement for the
     * update
     * @param string $sql
     * @param \golibdatabase\Database\MySql\WhereSet $subWhere
     * @return string
     */
    private function composeWhere ( $sql, WhereSet $subWhere ) {
        if ($this->useWhereFromTable) {
            $tabWhere = $this->table->getWhere();
            $tabWhere->applyWhere( $subWhere );
            $sql .= ' WHERE ' . $tabWhere->getWhereCondition();
        } else {
            $sql .= ' WHERE ' . $subWhere->getWhereCondition();
        }
        return $sql;
    }

    private function applyWhereByProps ( Props $origin, WhereSet $where ) {
        foreach ($origin as $field => $value) {
            if (!in_array( $field, $this->ignoreList ) && !in_array( $field,
                                                                     $this->ignoreWhereOnUpdate )) {
                $where->isEqual( $field, $value );
            }
        }
    }

    private function getStatement ( Diff\Row $diff ) {
        $val = $diff->value;
        if ($diff->value === \golib\Types\MapConst::TIMER) {
            $diff->value = '';
        }
        $statement = $this->table->getTableName()
                . '.'
                . $diff->name
                . ' = '
                . "'?'";
        $sql = new SimpleSql();
        $rowsql = $sql->sqlString( $statement, array(
            $diff->value) );
        return $rowsql;
    }

}
