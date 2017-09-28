<?php
namespace golibdatabase\Database\MySql;

/**
 * Description of TableException
 *
 * @author tziegler
 */
class TableException extends \Exception{
    const CODE_NOT_SINGLE = 1001;
    const MESSAGE_NOT_SINGLE = "There must be excactly one Result.";
}
