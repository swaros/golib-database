<?php

namespace golibdatabase\Database\Sql;

/**
 * Description of Expression
 *
 * @author tziegler
 */
class Expression
{
    private string|null $value = NULL;

    const NO_CONVERSION = false;
    const TABLE_NAME = 1;

    public function __construct(string $expression, bool $convertFlag = false)
    {
        if ($convertFlag !== false) {
            if ($convertFlag == self::TABLE_NAME) {
                $this->value = "`{$expression}`";
            } else {
                throw new \InvalidArgumentException("Unsupported Conversion Flag");
            }
        } else {
            $this->value = $expression;
        }
    }

    public function __toString()
    {
        return $this->value;
    }

}
