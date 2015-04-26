<?php
require_once("exceptions.php");

class Entity {
    private static $deleteQuery = 'DELETE FROM "%1$s" WHERE %1$s_id=?';
    private static $insertQuery = 'INSERT INTO "%1$s" (%2$s) VALUES (%3$s)';
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

    public function __construct($id=null) {
        self::initDatabase();

        // init some fields
    }

    public function __get($name) {
        // check, if instance is modified and throw an exception
        // get corresponding data from database if needed
        // check, if requested property name is in current class
        //    columns, parents, children or siblings and call corresponding
        //    getter with name as an argument
        // throw an exception, if attribute is unrecognized
    }

    public function __set($name, $value) {
        // check, if requested property name is in current class
        //    columns, parents, children or siblings and call corresponding
        //    setter with name and value as arguments or use default implementation
    }

    public function delete() {
        // execute delete query with appropriate id
    }

    public function getColumn($name) {
        // return value from fields array by <table>_<name> as a key
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
        // execute either insert or update query, depending on instance id
    }

    public function setColumn($name, $value) {
        // put new value into fields array with <table>_<name> as a key
    }


    public function setParent($name, $parent) {
        // put new value into fields array with <name>_id as a key
        // value can be a number or an instance of Entity subclass
    }

    public static function all() {
        self::initDatabase();
        // get ALL rows with ALL columns from corrensponding table
        // for each row create an instance of appropriate class
        // each instance must be filled with column data, a correct id and MUST NOT query a database for own fields any more
        // return an array of istances
    }

    public static function setDatabase(PDO $db) {
        // try to guess
    }

    private function execute($query, $args) {
        // execute an sql statement and handle exceptions together with transactions
    }

    private function insert() {
        // generate an insert query string from fields keys and values and execute it
        // use prepared statements
        // save an insert id
    }

    private function load() {
        // if current instance is not loaded yet — execute select statement and store it's result as an associative array (fields), where column names used as keys
    }

    private function update() {
        // generate an update query string from fields keys and values and execute it
        // use prepared statements
    }

    private static function initDatabase() {
        if ( self::$db === null ) {
            throw new DatabaseException();
        }
        self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
}
