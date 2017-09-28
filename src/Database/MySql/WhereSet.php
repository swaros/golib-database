<?php

namespace golibdatabase\Database\MySql;

use golibdatabase\Database\Sql\Expression;

/**
 * Description of WhereSet
 *
 * @author tziegler
 *
 * layer for building where statements
 *
 */
class WhereSet {

    /**
     * statements compared by AND
     */
    const USE_AND = 1;

    /**
     * stetements compared by or
     */
    const USE_OR = 2;

    /**
     * contains all fieldnames
     * that should be equal to given value
     * @var array
     */
    private $equals = array();

    /**
     *
     * @var string
     */
    private $in = array();

    /**
     *
     * @var string
     */
    private $notIn = array();

    /**
     * contains all fieldnames
     * that should be not equal to given value
     * @var array
     */
    private $notEquals = array();

    /**
     * contains all fieldnames
     * that should be greather then the given value
     * @var array
     */
    private $greater = array();

    /**
     * contains all fieldnames
     * that should be equal to given value
     * @var array
     */
    private $lower = array();

    /**
     * the current compare mode
     * @var string
     */
    private $compare = 'AND';

    /**
     * list of whereSet that have to be included
     * @var WhereSet[]
     */
    private $otherWhere = array();
    private $expressions = array();
    private $nullusage = true;

    /**
     *
     * @param int $state
     */
    public function __construct ( $state = self::USE_AND ) {
        if ($state == self::USE_OR) {
            $this->compare = 'OR';
        }
    }

    public function isEqual ( $name, $value ) {
        $this->equals[] = array(
            $name => $value);
    }

    public function isIn ( $name, $value ) {
        $this->in[] = array(
            $name => $value);
    }

    public function isNotIn ( $name, $value ) {
        $this->notIn[] = array(
            $name => $value);
    }

    public function isNotEqual ( $name, $value ) {
        $this->notEquals[] = array(
            $name => $value);
    }

    public function isGreater ( $name, $value ) {
        $this->greater[] = array(
            $name => $value);
    }

    public function isLower ( $name, $value ) {
        $this->lower[] = array(
            $name => $value);
    }

    public function applyWhere ( self $where ) {
        $this->otherWhere[] = $where;
    }

    public function expression ( Expression $expression ) {
        $this->expressions[] = $expression;
    }

    private function assign ( &$where, $key, $compare, $value ) {
        if ($value instanceof Expression) {
            $where[] = "`{$key}` {$compare} {$value}";
        } elseif ($key instanceof Expression) {
            $where[] = "$key";
        } elseif ($value === null && $this->nullusage) {
            $where[] = "`{$key}` is null ";
        } else {
            $where[] = "`{$key}` {$compare} '{$value}'";
        }
    }

    public function getWhereCondition () {
        $where = array();
        foreach ($this->equals as $match) {
            foreach ($match as $key => $value) {
                $this->assign( $where, $key, '=', $value );
            }
        }

        foreach ($this->notEquals as $match) {
            foreach ($match as $key => $value) {
                $this->assign( $where, $key, '!=', $value );
            }
        }

        foreach ($this->greater as $match) {
            foreach ($match as $key => $value) {
                $this->assign( $where, $key, '>', $value );
            }
        }

        foreach ($this->lower as $match) {
            foreach ($match as $key => $value) {
                $this->assign( $where, $key, '<', $value );
            }
        }

        foreach ($this->in as $match) {
            foreach ($match as $key => $value) {
                $value = $this->mapInStr( $value );
                $this->assign( $where, $key, 'in',
                               new Expression( "({$value})" ) );
            }
        }

        foreach ($this->notIn as $match) {
            foreach ($match as $key => $value) {
                $value = $this->mapInStr( $value );
                $this->assign( $where, $key, 'not in',
                               new Expression( "({$value})" ) );
            }
        }

        foreach ($this->expressions as $exprs) {
            $this->assign( $where, $exprs, '', '' );
        }

        foreach ($this->otherWhere as $whereObj) {
            $where[] = $whereObj->getWhereCondition();
        }

        $sqlAdd = '(' . implode( ' ' . $this->compare . ' ', $where ) . ')';
        return $sqlAdd;
    }

    private function mapInStr ( $value ) {
        $vals = explode( ',', $value );
        $vars = array();
        foreach ($vals as $ent) {
            $vars[] = "'" . addslashes( $ent ) . "'";
        }
        $value = implode( ',', $vars );
        return $value;
    }

}
