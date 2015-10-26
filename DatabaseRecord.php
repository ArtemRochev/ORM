<?php

require_once('SqlBuilder.php');

class DatabaseRecord {
    const DEFAULT_CHARSET = 'UTF8';
    const DEFAULT_LIMIT = 500;

    private static $db = null;
    protected static $parents = [];
    protected static $childrens = [];

    private $fields   = [];
    private $loaded   = false;
    private $modified = false;
    private $id       = null;
    private $table    = null;

    public function __construct($id = null) {
        self::checkDatabase();

        $this->id = $id;
        $this->table = strtolower(get_class($this));
    }

    public function __get($name) {
        if ( $this->modified ) {
            throw new InvalidOperationException;
        }

        $getMethod = 'get' . ucfirst($name);
        if ( method_exists($this, $getMethod) ) {
            return $this->$getMethod();
        }

        $this->load();

        return $this->getValue($name);
    }

    public function __set($name, $value) {
        $value = str_replace("'", "''", $value);
        $this->setValue($name, $value);
    }

    public function getValue($name) {
        $this->load();

        if ( $name == 'id' ) {
            return $this->id;
        }

        return $this->fields[$name];
    }

    public function setValue($name, $value) {
        $this->fields[$name] = $value;
        $this->modified = true;
    }

    public function getOne($className, $selfColumn, $otherColumn) {
        return $className::findOne([$otherColumn => $this->getValue($selfColumn)]);
    }

    public function getMany($className, $selfColumn, $otherColumn) {
        return $className::find([$otherColumn => $this->getValue($selfColumn)]);
    }

    public function getCount($table = '', $where = []) {
        if ( $table == '' ) {
            $table = $this->table;
        }

        $queryArgs = [];
        $query = self::buildSelectQuery($table, 'count(*) as count', $where, $queryArgs);

        return self::execute($query, $queryArgs, 'single')['count'];
    }

    public function getFields() {
        return $this->fields;
    }

    public static function getClass() {
        return get_called_class();
    }

    public function save() {
        if ( $this->modified || !$this->id ) {
            if ( isset($this->id ) ) {
                $this->update();
            } else {
                $this->insert();
            }
        }

        $this->modified = false;
    }

    public function delete() {
        if ( $this->id == NULL ) {
            throw new InvalidOperationException;
        }

        $query = sprintf(self::$deleteQuery, $this->table);

        self::execute($query, array($this->id));
    }

    public static function checkExists($table, $where) {
        $queryArgs = [];
        $query = self::buildSelectQuery($table, 'count(*) as count', $where, $queryArgs);

        return self::execute($query, $queryArgs, 'single');
    }

    public static function findOne($where = []) {
        if ( is_numeric($where) ) {
            $class = get_called_class();
            return new $class($where);
        }

        $class = get_called_class();
        $table = strtolower($class);
        $queryArgs = [];
        $query = SqlBuilder::buildSelectQuery($table, '*', $where, $queryArgs);
        $params = self::execute($query, $queryArgs, 'single');

        if ( !$params ) {
            return false;
        }

        return self::buildObject($params);
    }

    public static function all($selectedColumns = '*') {
        return self::find([], $selectedColumns);
    }

    public static function find($where = [], $select = '*') {
        $class = get_called_class();
        $table = strtolower($class);
        $queryArgs = [];

        $query  = SqlBuilder::buildSelectQuery($table, $select, $where, $queryArgs);
        $params = self::execute($query, $queryArgs, 'list');

        return self::buildObjectList($params);
    }

    private static function buildObject($params) {
        $class = get_called_class();
        $table = strtolower($class);

        $object = new $class;
        $object->id = $params['id'];
        $object->fields = $params;
        $object->loaded = true;
        $object->table = $table;

        return $object;
    }

    private static function buildObjectList($params) {
        $objList = [];

        foreach ( $params as $paramsPack) {
            $objList[] = self::buildObject($paramsPack);
        }

        return $objList;
    }



    public static function setDatabase(PDO $db, $charset = self::DEFAULT_CHARSET) {
        try {
            self::$db = $db;
        } catch (PDOException $e) {
            echo "Error connect to DB: " . $e->getMessage() . "\n";
        }

        self::setNames($charset);
        self::checkDatabase();
        self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public static function setNames($character = null) {
        if ( empty($character) ) {
            $character = self::$defaultCharacter;
        }

        self::execute(sprintf(SqlBuilder::$setNamesQuery, $character));
    }

    private static function execute($query, $args = [], $returningData = false) {
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

    private function insert() { // modify this method
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

        self::execute($query);
        $this->id = self::execute(self::$lastIdQuery, [], 'single');
        $this->loaded = true;
    }

    private function load() {
        if ( $this->id == NULL || $this->loaded ) {
            return false;
        }

        $queryArgs = [];
        $query = SqlBuilder::buildSelectQuery($this->table, '*', ['id' => $this->id], $queryArgs);
        $params = self::execute($query, $queryArgs, 'single');

        foreach ( $params as $column => $value ) {
            $this->fields[$column] = $value;
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
        self::execute($query, array($this->id));
    }

    private static function checkDatabase() {
        if ( self::$db === null ) {
            throw new DatabaseException();
        }
    }
}
