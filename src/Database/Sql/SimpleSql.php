<?php

namespace golibdatabase\Database\Sql;

/**
 * Description SimpleSql
 *
 * @author tziegler
 *
 * this class is just an placeholdermapper
 * for sql.
 *
 */
class SimpleSql {

    /**
     * all placeholders that should be converted to
     * default %s placeholder
     * @var type
     */
    private $replaces = array(
        '?');

    /**
     * all non default placeholders
     * that have to be converted
     * @param array $replaces
     */
    public function __construct ( $replaces = NULL ) {
        if ($replaces != NULL) {
            $this->replaces = $replaces;
        }
    }

    /**
     * sql string including placeholdes
     * @param string $sql
     * @param array $parameters
     * @return sting parsed sql string
     */
    public function sqlString ( $sql, $parameters = NULL ) {
        if (!is_array( $parameters )) {
            $parameters = array_slice( func_get_args(), 1 );
        }
        return $this->parsePlaceHolders( $sql, $parameters );
    }

    /**
     * mask string vars and replace all placeholder
     * @param string $sql
     * @param array $params
     * @return string
     */
    private function parsePlaceHolders ( $sql, array $params ) {
        if (!is_array( $params ) || empty( $params )) {
            return $sql;
        }

        $sqlNoQuestMark = str_replace( $this->replaces, "%s", $sql );
        $sqlB = $this->sprintfArras( $sqlNoQuestMark, $params );
        $sqlC = str_replace( "''", "'", $sqlB );


        return $sqlC;
    }

    /**
     * mapping for sprintf so
     * it can use arrays
     * @param type $format
     * @param type $arr
     * @return type
     */
    private function sprintfArras ( $format, $arr ) {

        foreach ($arr as &$val) {
            if (is_string( $val )) {
                $val = "'" . addslashes( stripslashes( $val ) ) . "'";
            }
        }

        return call_user_func_array( 'sprintf',
                                     array_merge( (array) $format, $arr ) );
    }

}
