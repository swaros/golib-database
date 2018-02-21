<?php
/**
 * Created by PhpStorm.
 * User: tziegler
 * Date: 20.02.18
 * Time: 14:40
 */

namespace golibdatabase\Database\MySql\Build;

use golib\Types\Props;

/**
 * Class ClassInfo
 * @package golibdatabase\Database\MySql\Build
 */
class ClassInfo extends Props
{
    public $namespace = '';
    public $classname = '';
    public $fullClassname = '';
    public $filepath = '';
    public $tableName = '';
}