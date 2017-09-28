<?php
namespace golibdatabase\Database\Sql;

/**
 * Description of Expression
 *
 * @author tziegler
 */
class Expression {
    private $value = NULL;

    const NO_CONVERSION = false;
    const TABLENAME = 1;

    public function __construct($expression, $convertFlag = false) {
        if ($convertFlag !== false){
            if ($convertFlag == self::TABLENAME){
                $this->value = "`{$expression}`";
            } else {
                throw new \InvalidArgumentException("Unsupported Conversion Flag");
            }
        } else {
            $this->value = $expression;
        }
    }

    public function __toString() {
        return $this->value;
    }

}
