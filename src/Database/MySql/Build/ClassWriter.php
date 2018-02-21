<?php
/**
 * Created by PhpStorm.
 * User: tziegler
 * Date: 20.02.18
 * Time: 14:41
 */

namespace golibdatabase\Database\MySql\Build;


/**
 * Class ClassWriter
 * @package golibdatabase\Database\MySql\Build
 */
class ClassWriter
{
    /**
     * basepath to folder that contains the templates
     * @var null|string
     */
    private $templatePath = null;

    /**
     * filename of the template for the main table Class
     * @var null|string
     */
    private $tableTemplate = 'TableClass.php';

    /**
     * filename of the template for fields - model
     * @var null|string
     */
    private $tableFieldTemplate = 'FieldPropClass.php';

    /**
     * @var bool|string
     */
    private $storageTable = '*';
    /**
     * @var bool|string
     */
    private $storageFields = '*';

    /**
     * @var int
     */
    private $tabspace = 4;

    /**
     * @var string
     */
    private $mainNamespace = 'Build\Database\Tables';
    /**
     * @var string
     */
    private $PropertieNamespace = 'Build\Database\DbModel';

    /**
     * @var null
     */
    private $currentPropClassName = NULL;
    /**
     * @var null
     */
    private $currentPrimary =  NULL;
    /**
     * @var bool
     */
    private $isRealPrimary =  false;

    /**
     * @var StorageInfo[]
     */
    private $fileStorage = array();

    /**
     * ClassWriter constructor.
     * @param null $basePath
     * @param null $tableTemplate
     * @param null $fieldsTemplate
     */
    public function __construct($basePath = NULL, $tableTemplate = NULL, $fieldsTemplate = NULL)
    {

        $this->templatePath = __DIR__ . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR;

        if ($basePath !== NULL){
            $this->templatePath = $basePath;
        }

        if ($tableTemplate !== NULL){
            $this->tableTemplate = $tableTemplate;
        }

        if ($fieldsTemplate !== NULL){
            $this->tableFieldTemplate = $fieldsTemplate;
        }
        $this->storageTable = $this->loadTemlates($this->templatePath . $this->tableTemplate);
        $this->storageFields = $this->loadTemlates($this->templatePath . $this->tableFieldTemplate);

    }

    /**
     * @param $path
     * @return bool|string
     */
    private function loadTemlates($path){
        if (file_exists($path) && is_readable($path)){
            $storage = file_get_contents($path);
            return $storage;
        }

        throw new \InvalidArgumentException("File {$path} not found or not readable");
    }

    /**
     * returns all created storage infos
     * @return StorageInfo[]
     */
    public function getFileStorage(){
        return $this->fileStorage;
    }


    /**
     * @param $tablename
     * @param FieldProp[] $fields
     */
    public function buildTableContent($tablename, array $fields){

        $this->buildFieldPropClass($tablename, $fields);
        $this->buildMainClass($tablename);
    }

    /**
     * @param $tablename
     */
    private function buildMainClass($tablename){
        $mainClassFile = new StorageInfo();
        $classInfo = NameResolve::toClassInfo($tablename);
        $classTemplate = $this->storageTable;

        $classTemplate = str_replace('[@NAMESPACE]'
            , NameResolve::chainNs($this->mainNamespace, $classInfo->namespace)
            , $classTemplate
        );

        $classTemplate = str_replace('[@CLASSNAME]'
            , $classInfo->classname
            , $classTemplate
        );

        $classTemplate = str_replace('[@TABLENAME]'
            , $tablename
            , $classTemplate
        );

        $classTemplate = str_replace('[@FIELDCLASS]'
            , $this->currentPropClassName
            , $classTemplate
        );

        $classTemplate = str_replace('[@PRIMARY]'
            , $this->currentPrimary
            , $classTemplate
        );

        $mainClassFile->content = $this->cleanupSource($classTemplate);
        $mainClassFile->path = str_replace('\\', DIRECTORY_SEPARATOR, $this->mainNamespace)
            . DIRECTORY_SEPARATOR
            . $classInfo->filepath;


        $this->fileStorage[] = $mainClassFile;

    }

    /**
     * @param $tablename
     * @param array $fields
     */
    private function buildFieldPropClass($tablename, array $fields){
        $propClassStorage = new StorageInfo();
        $this->isRealPrimary = false;
        $this->currentPrimary = NULL;
        $classInfo = NameResolve::toClassInfo($tablename);

        $fieldNamesCode = '';
        foreach ($fields as $field){
            $this->checkPrimary($field);
            $fieldNamesCode .= $this->getFieldEntry($field);
        }


        $fieldTemplate = $this->storageFields;

        $fieldTemplate = str_replace('[@PROPS]',$fieldNamesCode,  $fieldTemplate);
        $fieldTemplate = str_replace('[@CLASSNAME]'
            , $classInfo->classname
            , $fieldTemplate);
        $fieldTemplate = str_replace('[@NAMESPACE]'
            , NameResolve::chainNs($this->PropertieNamespace,$classInfo->namespace)
            , $fieldTemplate);
        $this->currentPropClassName = NameResolve::chainNs($this->PropertieNamespace,$classInfo->namespace)
            . '\\'
            . $classInfo->classname;

        $propClassStorage->content = $this->cleanupSource($fieldTemplate);
        $propClassStorage->path = str_replace('\\', DIRECTORY_SEPARATOR, $this->PropertieNamespace)
            . DIRECTORY_SEPARATOR
            . $classInfo->filepath;

        $this->fileStorage[] = $propClassStorage;

    }

    /**
     * @param $source
     * @return mixed
     */
    private function cleanupSource($source){
        return str_replace(array('/*--','--**/','--*/'),'',$source);
    }

    /**
     * @param $str
     * @return mixed
     */
    private function getType($str){
        return current(explode('(',str_replace(' ','(',$str)));
    }

    /**
     * @param FieldProp $field
     */
    private function checkPrimary(FieldProp $field){
        if ($this->currentPrimary === NULL){
            $this->currentPrimary = $field->Field;
        }

        if ($this->isRealPrimary === false && $field->Key == 'PRI'){
            $this->currentPrimary = $field->Field;
            $this->isRealPrimary = true;
        }

    }

    /**
     * @param FieldProp $field
     * @return string
     */
    private function getFieldEntry(FieldProp $field){
        $tab = str_repeat(' ',$this->tabspace);

        $type = $this->getType($field->Type);
        $str = $tab . '/** origin type '.$field->Type.' */' . PHP_EOL;
        switch ($type){
            case 'int';
            case 'tinyint';
            case 'mediumint';
            case 'smallint';
            case 'decimal';
            case 'float';
            case 'double';
            case 'bit';
            case 'bigint';
                $str .= $tab . 'public $' . $field->Field . ' = 0;' . PHP_EOL;
                break;
            case 'varchar':
            case 'text':
            case 'mediumtext':
            case 'longtext':
            case 'set':
            case 'enum':
            case 'char':
                $str .= $tab . 'public $' . $field->Field . ' = \'\';' . PHP_EOL;
                break;
            case 'date':
            case 'datetime':
            case 'time':
            case 'timestamp':
                $str = $tab . '/** @var Set\Timer  Origin '.$field->Type.' */' . PHP_EOL;
                $str .= $tab . 'public $' . $field->Field . ' = Set\MapConst::Timer;' . PHP_EOL;
                break;
            default:
                //$str .= "//[$type]". PHP_EOL;
                $str .= $tab . 'public $' . $field->Field . ' = NULL;' . PHP_EOL;
        }
        return $str;
    }
}