<?php

class DatabaseRecord {
    private static $deleteQuery =       'DELETE FROM `%s` WHERE id=?';
    private static $insertQuery =       'INSERT INTO `%1$s` (%2$s) VALUES (%3$s)';
    private static $selectQuery =       'SELECT %s FROM `%s`';
    private static $selectByIdQuery =   'SELECT * FROM `%s` WHERE id=?'; //merge with other query?
    private static $updateQuery =       'UPDATE `%1$s` SET %2$s WHERE id=?';
    private static $lastIdQuery =       'SELECT LAST_INSERT_ID()';
    private static $setNamesQuery =      'SET NAMES %s';
    
    private static $defaultCharacter = 'UTF8';
    private static $db = null;

    private $fields   = [];
    private $loaded   = false;
    private $modified = false;
    private $id       = null;
    private $class    = null;
    private $table    = null;
    
    public function __construct($id = null) {
        self::checkDatabase();

        $this->id = $id;
        $this->class = get_class($this);
        $this->table = strtolower($this->class);
    }

    public function __get($name) {
        if ( $this->modified ) {
            throw new InvalidOperationException;
        }
        
        $this->load();

        if ( $name == $this->parent ) {
            return $this->getParent($name);
        }
        if ( in_array($name, $this->childrens) ) {
            return $this->getChildren($name);
        }
        
        return $this->getColumn($name);
    }

    public function __set($name, $value) {
        $value = str_replace("'", "''", $value);
        $this->setColumn($name, $value);
    }

    public function getColumn($name) {
        if ( $name == 'id' ) {
            return $this->id;
        }
        if ( $name == $this->parent ) {

            if ( isset($this->fields[$name . '_id']) ) {
                return $this->fields[$name . '_id'];
            } else {
                return $this->fields[$this->table . '_' . $name . '_id'];
            }
        }

        return $this->fields[$name];
    }

    public function setColumn($name, $value) {
        $this->fields[$name] = $value;
        $this->modified = true;
    }

    public function getParent($name) {
        return new User($this->getColumn($name)); //fix
    }

    public function getChildren($name) {
        $class = ucfirst($name);

        return new $class;
    }

    public function getCount($table = '', $where = []) {
        if ( $table == '' ) {
            $table = $this->table;
        }

        $queryArgs = [];
        $query = self::buildSelectQuery($table, 'count(*) as count', $where, $queryArgs);

        return self::execute($query, $queryArgs, 'single')['count'];
    }

    public function save() {
        if ( $this->modified || !$this->id ) {
            if ( !$this->id ) {
                $this->insert();
            } else {
                $this->update();
            }
        }

        $this->modified = false;
    }

    public function delete() {
        if ( $this->id == NULL ) {
            throw new InvalidOperationException;
        }
        
        $query = sprintf(self::$deleteQuery, $this->table);

        $this->execute($query, array($this->id));
    }
    
    public static function checkExists($table, $where) {
        $queryArgs = [];
        $query = self::buildSelectQuery($table, 'count(*) as count', $where, $queryArgs);

        return self::execute($query, $queryArgs, 'single');
    }

    public static function findById($id) {
        $class = get_called_class();

        return new $class($id);
    }

    public static function findOne($where = []) {
        if ( is_numeric($where) ) {
            self::findById($where);
        }

        $type = get_called_class();
        $table = strtolower($type);
        $queryArgs = [];
        $query = self::buildSelectQuery($table, 'id', $where, $queryArgs);

        return new $type(self::execute($query, $queryArgs, 'single')['id']); //fix
    }
    
    public static function all($select = '*') {
        return self::allWhere([], $select);
    }

    public static function allWhere($where = [], $select = '*') {
        $type = get_called_class();
        $table = strtolower($type);
        $queryArgs = [];

        $query  = self::buildSelectQuery($table, $select, $where, $queryArgs);
        $params = self::execute($query, $queryArgs, 'list');

        return self::buildObject($params);
    }

    private static function buildObject($params) {
        $class = get_called_class();
        $table = strtolower($class);
        $objList = [];

        foreach ( $params as $index => $parameterPack) {
            $obj = new $class;
            $obj->id = $parameterPack['id'];
            $obj->fields = $parameterPack;
            $obj->loaded = true;
            $obj->table = $table;

            $objList[] = $obj;
        }

        if ( count($objList) == 1 ) {
            return $objList[0];
        }

        return $objList;
    }

    private static function buildSelectQuery($from, $select = '*', $where = [], &$args = []) {
        $query = sprintf(self::$selectQuery, $select, $from);

        if ( !empty($where) ) {
            $query .= self::buildWherePartQuery($where, $args);
        }

        return $query;
    }

    private static function buildWherePartQuery($params, &$whereValues = []) {
        $where = ' WHERE';

        foreach( $params as $column => $value ) {
            $where .= ' ' . $column . '=? AND';
            $whereValues[] = $value;
        }

        return rtrim($where, 'AND'); //fix
    }

    public static function setDatabase(PDO $db) {
        try {
            self::$db = $db;
        } catch (PDOException $e) {
            echo "Error connect to DB: " . $e->getMessage() . "\n";
        }
        
        self::setNames();
        self::checkDatabase();
        self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public static function setNames($character = null) {
        if ( empty($character) ) {
            $character = self::$defaultCharacter;
        }
        
        self::execute(sprintf(self::$setNamesQuery, $character));
    }
    
    private function execute($query, $args = [], $returningData = false) {
        self::checkDatabase();

        $query = self::$db->prepare($query);

        try {
            $query->execute($args);
        } catch (PDOException $e) {
            echo $e->getMessage() . "\n";
        }
        
        if ( $returningData == 'single' ) {
            return $query->fetch(PDO::FETCH_ASSOC);
        } else if ( $returningData == 'list' ) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    private function insert() {
        $fieldsStr = '';
        $valuesStr = '';
        
        foreach ($this->fields as $field => $value) {
            $fieldsStr .= $field . ",";
            $valuesStr .= "'" . $value . "',";
        }
        $fieldsStr = rtrim($fieldsStr, ",");
        $valuesStr = rtrim($valuesStr, ",");

        $query = sprintf(
            self::$insertQuery,
            $this->table,
            $fieldsStr,
            $valuesStr
        );
        
        $this->execute($query);
        $this->id = $this->execute(self::$lastIdQuery, [], 'single');
        $this->loaded = true;
    }

    private function load() {
        if ( $this->id == NULL || $this->loaded ) {
            return false;
        }

        $row = $this->execute(sprintf(self::$selectByIdQuery, $this->table), [$this->id], 'single');

        foreach ($this->columns as $column) {
            $this->fields[$column] = $row[$column];
        }

        $this->modified = false;
        $this->loaded = true;
        
        return true;
    }

    private function update() {
        $modifiedFieldsStr = '';
        
        foreach ($this->fields as $field => $value) {
            $modifiedFieldsStr .= $field . "='" . $value . "',";
        }
        $modifiedFieldsStr = rtrim($modifiedFieldsStr, ",");
        
        $query = sprintf(self::$updateQuery, $this->table, $modifiedFieldsStr);
        $this->execute($query, array($this->id));
    }

    private static function checkDatabase() {
        if ( self::$db === null ) {
            throw new DatabaseException();
        }
    }
}
