<?php
require_once("exceptions.php");

class DatabaseRecord {
    private static $deleteQuery = 'DELETE FROM `%1$s` WHERE %1$s_id=?';
    private static $insertQuery = 'INSERT INTO `%1$s` (%2$s) VALUES (%3$s)';
    private static $listQuery   = 'SELECT * FROM `%s`';
    private static $selectQuery = 'SELECT * FROM `%1$s` WHERE %1$s_id=?';
    private static $updateQuery = 'UPDATE `%1$s` SET %2$s WHERE %1$s_id=?';
    private static $lastIdQuery = 'SELECT LAST_INSERT_ID()';
    private static $charsetQuery = 'SET NAMES %1$s';
    
    private static $defaultEncording = 'utf8';
    private static $db = null;

    private $fields   = [];
    private $loaded   = false;
    private $modified = false;
    private $id       = null;
    private $class    = null;
    private $table    = null;
    
    public function __construct($id = null) {
        self::initDatabase();
        
        $this->id = $id;
        $this->class = get_class($this);
        $this->table = strtolower($this->class);
    }

    public function __get($name) {
        //echo "__get(): $name\n";
        
        if ( $this->modified ) {
            throw new InvalidOperationException;
        }
        
        $this->load();
        
        if ( $name == $this->parent ) {
            return $this->getParent($this->parent);
        }
        
        return $this->getColumn($name);
    }

    public function __set($name, $value) {
    	$value = str_replace("'", "''", $value);
        $this->setColumn($name, $value);
    }

    public function delete() {
        if ( $this->id == NULL ) {
            throw new InvalidOperationException;
        }
        
        $query = sprintf(self::$deleteQuery, $this->table);
        $this->execute($query, array($this->id), false);
    }

    public function getColumn($name) {
        return $this->fields[$this->table . "_" . $name];
    }

    public function getParent($name) {
        //echo "getParent()\n";
        
        $user = new User($this->getColumn($name . "_id"));
        
        return $user;
    }
    
    public function save() {
        if ( $this->modified ) {
            if ( $this->id == NULL ) {
                $this->insert();
            } else {
                $this->update();
            }
        }
        
        $this->modified = false;
    }

    public function setColumn($name, $value) {
    	$this->fields[$this->table . "_" . $name] = $value;
    	$this->modified = true;
    }
    
    public static function all() {
        self::initDatabase();
        
        $type = get_called_class();
        $table = strtolower($type);
        $objList = [];
        $rowCount;
        
        //echo sprintf(self::$listQuery, $table);
        
        $list = self::$db->query(sprintf(self::$listQuery, $table));
        $list = $list->fetchAll(PDO::FETCH_ASSOC);
        $rowCount = count($list);
            
        for ( $i = 0; $i < $rowCount; $i++ ) {
            $obj = new $type;
            $obj->fields = $list[$i];
            $obj->loaded = true;
            
            $objList[] = $obj;
        }
        
        return $objList;
    }

    public static function setDatabase(PDO $db) {
        try {
            self::$db = $db;
        } catch (PDOException $e) {
            echo "Error connect to DB: " . $e->getMessage() . "\n";
        }
        
        self::setEncording();
    }

    public static function setEncording($encording = null) {
        if ( !$encording ) {
            $encording = self::$defaultEncording;
        }
        
        self::execute(sprintf(self::$charsetQuery, $encording), array(), false);
    }
    
    private function execute($query, $args, $isReturningData = true) {
        $query = self::$db->prepare($query);
        
        try {
            $query->execute($args);
        } catch (PDOException $e) {
            echo $e->getMessage() . "\n";
        }
        
        if ( $isReturningData ) {
            return $query->fetch(PDO::FETCH_ASSOC);
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
        
        $this->execute($query, array(), false);
        $this->id = $this->execute(self::$lastIdQuery, array())['LAST_INSERT_ID()'];
        $this->loaded = true;
    }

    private function load() {
        if ( $this->id == NULL || $this->loaded ) {
            return false;
        }
        
        $row = $this->execute(sprintf(self::$selectQuery, $this->table), array($this->id));
        
        foreach ($this->columns as $column) {
            $column = $this->table . "_" . $column;
            
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
        $this->execute($query, array($this->id), false);
    }

    private static function initDatabase() {
        if ( self::$db === null ) {
            throw new DatabaseException();
        }
        self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
}
