<?php
require_once("exceptions.php");

class Entity {
    private static $deleteQuery = 'DELETE FROM "%1$s" WHERE %1$s_id=?';
    private static $insertQuery = 'INSERT INTO "%1$s" (%2$s) VALUES (%3$s) RETURNING "%1$s_id"';
    private static $listQuery   = 'SELECT * FROM "%s"';
    private static $selectQuery = 'SELECT * FROM "%1$s" WHERE %1$s_id=?';
    private static $updateQuery = 'UPDATE "%1$s" SET %2$s WHERE %1$s_id=?';

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
        if ( $this->modified ) {
            throw new InvalidOperationException;
        }
        
        $this->load();
        
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
        $this->execute($query, array($this->id));
    }

    public function getColumn($name) {
        return $this->fields[$this->table . "_" . $name];
    }


    public function getChildren($name) {
        // return an array of child entity instances
        // each child instance must have an id and be filled with data
    }

    public function getParent($name) {
        // get parent id from fields with <name>_id as a key
        // return an instance of parent entity class with an appropriate id
    }

    public function getSiblings($name) {
        // get parent id from fields with <name>_id as a key
        // return an array of sibling entity instances
        // each sibling instance must have an id and be filled with data
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
        // $name = $this->table . "_" . $name;
        
        // if ( $this->loaded ) {
        //     if ( $this->fields[$name] == $value ) {
        //         return false;
        //     }
        // }
        
        // $value = str_replace("'", "''", $value);
        // $this->fields[$name] = $value;
        
        // return true;
    }


    public function setParent($name, $parent) {
        // put new value into fields array with <name>_id as a key
        // value can be a number or an instance of Entity subclass
    }

    public static function all() {
        self::initDatabase();
        
        $type = get_called_class();
        $table = strtolower($type);
        $objList = [];
        $rowCount;
        
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
    }

    private function execute($query, $args) {
        $query = self::$db->prepare($query);
                
        try {
            $query->execute($args);
        } catch (PDOException $e) {
            echo $e->getMessage() . "\n";
        }
        
        return $query->fetch(PDO::FETCH_ASSOC);
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
        
        $query = sprintf(self::$insertQuery, $this->table, $fieldsStr, $valuesStr);
        
        $this->id = $this->execute($query, array())[$this->table . "_id"];
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
        $this->execute($query, array($this->id));
    }

    private static function initDatabase() {
        if ( self::$db === null ) {
            throw new DatabaseException();
        }
        self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
}
