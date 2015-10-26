<?php

class SqlBuilder {
    public static $deleteQuery =       'DELETE FROM `%s` WHERE id=?';
    public static $insertQuery =       'INSERT INTO `%s` (%s) VALUES (%s)';
    public static $selectQuery =       'SELECT %s FROM `%s`';
    public static $selectByIdQuery =   'SELECT * FROM `%s` WHERE id=?'; //merge with other query?
    public static $updateQuery =       'UPDATE `%s` SET %s WHERE id=?';
    public static $lastIdQuery =       'SELECT LAST_INSERT_ID()';
    public static $setNamesQuery =     'SET NAMES %s';

    public static function buildSelectQuery($from, $select = '*', $where = [], &$args = []) {
        $query = sprintf(self::$selectQuery, $select, $from);

        if ( !empty($where) ) {
            $query .= self::buildWherePartQuery($where, $args);
        }

        return $query;
    }

    public static function buildWherePartQuery($params, &$whereValues = []) {
        $where = ' WHERE';

        foreach( $params as $column => $value ) {
            $where .= ' ' . $column . '=? AND';
            $whereValues[] = $value;
        }

        return rtrim($where, 'AND'); //fix
    }
}