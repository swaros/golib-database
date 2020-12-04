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
     * statements compared by or
     */
    const USE_OR = 2;

    /**
     * contains all fieldnames
     * that should be equal to given value
     * @var array
     */
    private array $equals = array();

    /**
     *
     * @var array
     */
    private array $in = array();

    /**
     *
     * @var array
     */
    private array $notIn = array();

    /**
     * contains all fieldnames
     * that should be not equal to given value
     * @var array
     */
    private array $notEquals = array();

    /**
     * contains all fieldnames
     * that should be greather then the given value
     * @var array
     */
    private array $greater = array();

    /**
     * contains all fieldnames
     * that should be equal to given value
     * @var array
     */
    private array $lower = array();

    /**
     * the current compare mode
     * @var string
     */
    private string $compare = 'AND';

    /**
     * list of whereSet that have to be included
     * @var WhereSet[]
     */
    private array $otherWhere = array();
    private array $expressions = array();
    private bool $nullUsage = true;

    /**
     *
     * @param int $state
     */
    public function __construct ( $state = self::USE_AND ) {
        if ($state == self::USE_OR) {
            $this->compare = 'OR';
        }
    }

    public function isEqual ( $name, $value ): self {
        $this->equals[] = array($name => $value);
        return $this;
    }

    public function isIn ( $name, $value ): self {
        $this->in[] = array($name => $value);
        return $this;
    }

    public function isNotIn ( $name, $value ): self {
        $this->notIn[] = array($name => $value);
        return $this;
    }

    public function isNotEqual ( $name, $value ) {
        $this->notEquals[] = array($name => $value);
        return $this;
    }

    public function isGreater ( $name, $value ): self {
        $this->greater[] = array($name => $value);
        return $this;
    }

    public function isLower ( $name, $value ): self {
        $this->lower[] = array($name => $value);
        return $this;
    }

    public function applyWhere ( self $where ): self {
        $this->otherWhere[] = $where;
        return $this;
    }

    public function expression ( Expression $expression ): self {
        $this->expressions[] = $expression;
        return $this;
    }

    private function assign ( &$where, $key, $compare, $value ): self {
        if ($value instanceof Expression) {
            $where[] = "`{$key}` {$compare} {$value}";
        } elseif ($key instanceof Expression) {
            $where[] = "$key";
        } elseif ($value === null && $this->nullUsage) {
            $where[] = "`{$key}` is null ";
        } else {
            $where[] = "`{$key}` {$compare} '{$value}'";
        }
        return $this;
    }

    public function getWhereCondition (): string {
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

        return '(' . implode( ' ' . $this->compare . ' ', $where ) . ')';
    }

    private function mapInStr ( string $value ): string {
        $vals = explode( ',', $value );
        $vars = array();
        foreach ($vals as $ent) {
            $vars[] = "'" . addslashes( $ent ) . "'";
        }
        $value = implode( ',', $vars );
        return $value;
    }

}
