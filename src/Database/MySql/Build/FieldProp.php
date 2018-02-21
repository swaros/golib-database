<?php
/**
 * Created by PhpStorm.
 * User: tziegler
 * Date: 20.02.18
 * Time: 14:41
 */

namespace golibdatabase\Database\MySql\Build;
use golib\Types;


/**
 * Class FieldProp
 * @package golibdatabase\Database\MySql\Build
 */
class FieldProp extends Types\Props
{
    /**
     * @var null
     */
    public $Field = NULL;
    /**
     * @var null
     */
    public $Type = NULL;
    /**
     * @var null
     */
    public $Null = NULL;
    /**
     * @var null
     */
    public $Key = NULL;
    /**
     * @var null
     */
    public $Default = NULL;
    /**
     * @var null
     */
    public $Extra = NULL;
}