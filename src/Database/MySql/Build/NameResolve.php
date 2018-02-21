<?php
/**
 * Created by PhpStorm.
 * User: tziegler
 * Date: 20.02.18
 * Time: 14:42
 */

namespace golibdatabase\Database\MySql\Build;


/**
 * Class NameResolve
 * @package golibdatabase\Database\MySql\Build
 */
class NameResolve
{
    /**
     * @var string
     */
    private static $NS = '\\';

    /**
     * @var array
     */
    private static $names = array();

    /**
     * @var string
     */
    private static $clname = '';

    /**
     * builds class info depending on Table name
     * @param $tableName
     * @return ClassInfo
     */
    public static function toClassInfo($tableName){
        $class = new ClassInfo();
        $class->fullClassname = self::toClassName($tableName);
        $class->classname = self::$clname;
        $class->tableName = $tableName;
        $class->filepath = str_replace(self::$NS, DIRECTORY_SEPARATOR,$class->fullClassname). '.php';
        if (!empty(self::$names) && count(self::$names)>1){
            $class->namespace = implode(self::$NS,array_splice(self::$names,0,-1));
        }
        return $class;

    }

    /**
     * builds a valid ClassName depending on a table name
     * @param $tableName
     * @return string
     */
    public static function toClassName($tableName){
        self::$names = array();

        $NS_spilt = self::$NS;
        $name = str_replace(array('__','_','-'),$NS_spilt,$tableName);

        $nsGet = explode($NS_spilt,$name);
        if (is_array($nsGet) && count($nsGet)> 0){
            $nsName = array();
            foreach ($nsGet as $sub){
                $nsName[] = self::solveUpperCase($sub);
            }
            self::$names = $nsName;
            self::$clname = end($nsName);
            return implode($NS_spilt,$nsName);
        }
        self::$clname = self::solveUpperCase($name);
        return self::$clname;
    }

    /**
     * @param $string
     * @return string
     */
    private static function solveUpperCase($string){
        if (strlen($string) > 2 && strtoupper($string) == $string){
            return ucfirst(strtolower($string));
        }
        return ucfirst($string);
    }

    /**
     * @param $main
     * @param $add
     * @return string
     */
    public static function chainNs($main, $add){
        if (!$add){
            return $main;
        }
        return $main . '\\' . $add;
    }
}