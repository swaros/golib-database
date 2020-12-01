<?php

namespace golibdatabase\Database\Sql;

/**
 * Description SimpleSql
 *
 * @author tziegler
 *
 * this class is just an placeholder mapper
 * for sql.
 *
 */
class SimpleSql
{

    /**
     * all placeholders that should be converted to
     * default %s placeholder
     * @var array
     */
    private array $replaces = array('?');

    /**
     * all non default placeholders
     * that have to be converted
     * @param array|null $replaces
     */
    public function __construct(array|null $replaces = NULL)
    {
        if ($replaces != NULL) {
            $this->replaces = $replaces;
        }
    }

    /**
     * sql string including placeholdes
     * @param string $sql
     * @param array|string|null $parameters
     * @return string parsed sql string
     */
    public function sqlString(string $sql,array|string|null $parameters = NULL): string
    {
        if (!is_array($parameters)) {
            $parameters = array_slice(func_get_args(), 1);
        }
        return $this->parsePlaceHolders( $sql, $parameters );
    }

    /**
     * mask string vars and replace all placeholder
     * @param string $sql
     * @param array $params
     * @return string
     */
    private function parsePlaceHolders(string $sql, array $params)
    {
        if (!is_array($params) || empty($params)) {
            return $sql;
        }

        $sqlNoQuestMark = str_replace($this->replaces, "%s", $sql);
        $sqlB = $this->sprintfArras($sqlNoQuestMark, $params);
        return str_replace("''", "'", $sqlB);
    }

    /**
     * mapping for sprintf so
     * it can use arrays
     * @param string $format
     * @param array $arr
     * @return string
     */
    private function sprintfArras(string $format, array $arr): string
    {

        foreach ($arr as &$val) {
            if (is_string($val)) {
                $val = "'" . addslashes(stripslashes($val)) . "'";
            }
        }

        return call_user_func_array('sprintf',
            array_merge((array)$format, $arr));
    }

}
