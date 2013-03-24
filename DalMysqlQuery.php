<?php
/**
 * Database Abstraction Layer for PHP
 *
 * (C) 2009-2013 Nikolay Neizvesny
 *
 * Distributed under MIT license http://opensource.org/licenses/MIT
 */

/**
 * Database exception
 */
class DalException extends Exception {}

/**
 * Basic query
 */
abstract class DalBasicQuery {

    public $sql = '';
    public $cfg = null;
    public $classname;

    /**
     * Set class name for created objects
     * @param string $class
     * @return DalBasicQuery
     */
    public function setClass($class) {
        $this->classname = $class;
        return $this;
    }

    /**
     * Replace placeholders with values
     * @param string $text
     * @param array $args
     * @return string
     */
    public function parse($text, $args) {
        $i = 0;
        $me = $this;
        $text = preg_replace_callback('|#\?|', function() use (&$i, $args, $me) {
            return $me->quoteName($args[$i++]);
        }, $text);
        return preg_replace_callback('|\?|', function() use (&$i, $args, $me) {
            return $me->quote($args[$i++]);
        }, $text);
    }

    /**
     * Quote database name
     * @param string $name
     * @return string
     */
    public function quoteName($name) {
       return $name;
    }


    /**
     * Append query text from array of arguments
     * @param array $args
     * @return DalBasicQuery
     */
    public function queryArgs(array $args) {
        if (count($args) > 1) {
            $this->sql .= $this->parse($args[0], array_slice($args, 1)) . "\n";
        } else {
            $this->sql .= $args[0] . "\n";
        }
        return $this;
    }

    /**
     * Append query text and replace placeholders
     * @param string $text
     * @return DalBasicQuery
     */
    public function query($text) {
        if (func_num_args() > 1) {
            $this->sql .= $this->parse($text, array_slice(func_get_args(), 1)) . "\n";
        } else {
            $this->sql .= $text . "\n";
        }
        return $this;
    }

    /**
     * Append query if condition is positive
     * @param mixed $condition
     * @param string $text
     * @return DalBasicQuery
     */
    public function ifQuery($condition, $text) {
        if ($condition) {
            $args = array_slice(func_get_args(), 1);
            return $this->queryArgs($args);
        }
        return $this;
    }

    /**
     * Get sql text
     * @return string
     */
    public function asSql() {
        return $this->sql;
    }

    /**
     * Magically append query with method name and text, replace placeholders
     * @param string $name
     * @param array $args
     * @return DalBasicQuery
     */
    public function __call($name, $args) {
        $line = $name;
        $offset = 0;
        $words = array();
        while(preg_match('/([A-Za-z][a-z]*)(_*)/', $line, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $words []= strtoupper($m[1][0]);
            $offset = $m[0][1] + strlen($m[0][0]);
        }
        $args[0] = implode(' ', $words) . (isset($args[0]) ? (' ' . $args[0]) : '');
        return $this->queryArgs($args);
    }

    // Shortcut functions

    /**
     * Fetch row as object
     * @param string $class Result type
     * @return object
     */
    public function fetchRow($class = null) {
        return call_user_func_array(array($this, 'fetchObject'), func_get_args());
    }

    /**
     * Fetch all rows as objects
     * @param string $class Result objects type
     * @return array
     */
    public function fetchAll($class = null) {
        return call_user_func_array(array($this, 'fetchAllObject'), func_get_args());
    }

    /**
     * Append query text and replace placeholders
     * @param string $text
     * @return DalBasicQuery
     */
    public function q($text) {
        return $this->queryArgs(func_get_args());
    }

    /**
     * Append query if condition is positive
     * @param mixed $condition
     * @param string $text
     * @return DalBasicQuery
     */
    public function ifQ($condition, $text) {
        return call_user_func_array(array($this, 'ifQuery'), func_get_args());
    }

    // Abstract methods

    /**
     * Quete database value
     * @param mixed $val
     * @return string
     */
    abstract public function quote($val);

    /**
     * Connect to database
     * @param object $cfg
     * @return mixed
     */
    abstract public function connect($cfg = null);

    /**
     * Disconnect from database
     */
    public function disconnect() {}

    /**
     * Execute query
     * @param bool $last_id
     * @return DalBasicQuery
     */
    abstract public function exec($last_id = false);

    /**
     * Get last inserted id
     * @return mixed
     */
    abstract public function lastId();

    /**
     * Get affected rows
     * @return int
     */
    abstract public function affectedRows();

    /**
     * Fetch single value from database
     * @return mixed
     */
    abstract public function fetchCell();

    /**
     * Fetch row as object
     * @param string $class Result type
     * @return object
     */
    abstract public function fetchObject($class = null);

    /**
     * Fetch row as array
     * @return array
     */
    abstract public function fetchArray();

    /**
     * Fetch row as associative array
     * @return array
     */
    abstract public function fetchAssoc();

    /**
     * Fetch all rows as array of objects
     * @param string $class Result type
     * @return array
     */
    abstract public function fetchAllObject($class = null);

    /**
     * Fetch all rows as array of arrays
     * @return array
     */
    abstract public function fetchAllArray();

    /**
     * Fetch all rows as array of associative arrays
     * @return array
     */
    abstract public function fetchAllAssoc();

    /**
     * Fetch row as object from database
     * @param string $table
     * @param string $field Field in where clause
     * @param string $value Value in where clause
     * @param string $class Result type
     * @return object
     */
    abstract public function getObject($table, $field, $value, $class = null);

    /**
     * Fetch row as array from database
     * @param string $table
     * @param string $field Field in where clause
     * @param string $value Value in where clause
     * @return array
     */
    abstract public function getArray($table, $field, $value);

    /**
     * Fetch row as associative array from database
     * @param string $table
     * @param string $field Field in where clause
     * @param string $value Value in where clause
     * @return array
     */
    abstract public function getAssoc($table, $field, $value);
}

class DalMysqlQuery extends DalBasicQuery {

    public $connection = null;
    public $result = null;

    /**
     * Construct query from configuration or another DalMysqlQuery object
     * @param object $init Configuration object (host, user, password, dbname) or DalMysqlQuery
     */
    public function __construct($init = null) {
        if (get_class($init) == 'DalMysqlQuery') {
            $this->cfg = $init->cfg;
            $this->connection = $init->connection;
        } else {
            $this->cfg = $init;
            if ($init) $this->connect();
        }
    }

    /**
     * Create new query with same connection
     * @return DalMysqlQuery
     */
    public function __invoke() {
        $this2 = new DalMysqlQuery($this);
        return $this2;
    }

    /**
     * Connect to database
     * @param object $cfg Configuration object (host, user, password, dbname)
     * @return DalMysqlQuery
     * @throws DalException
     */
    public function connect($cfg = null) {
        if ($cfg) $this->cfg = $cfg;
        if ($this->connection) return $this;
        $this->connection = new mysqli(
            $this->cfg->host,
            $this->cfg->user,
            $this->cfg->password,
            $this->cfg->dbname
        );
        if (!$this->connection || $this->connection->connect_errno) {
            throw new DalException('Connection failed: ' . mysqli_connect_error());
        }
        return $this;
    }

    /**
     * Disconnect from database
     */
    public function disconnect() {
        if ($this->connection) $this->connection->close();
    }

    /**
     * Quote database name
     * @param string $name
     * @return string
     */
    public function quoteName($name) {
        return '`' . $name . '`';
    }

    /**
     * Quote database value
     * @param mixed $val
     * @return string
     */
    public function quote($val) {
        if ($val === null) return 'NULL';
        if ($val === false) return '0';
        if ($val === true) return '1';
        if (is_int($val)) return (string)$val;
        return "'" . $this->connection->real_escape_string($val) . "'";
    }

    /**
     * Select query
     * @param string $what
     * @return DalMysqlQuery
     */
    public function select($what = '*') {
        $args = func_get_args();
        $args[0] = 'SELECT ' . $what;
        return $this->queryArgs($args);
    }

    /**
     * Select all query
     * @return DalMysqlQuery
     */
    public function selectFrom() {
        $args = func_get_args();
        $args[0] = 'SELECT * FROM ' . $args[0];
        return $this->queryArgs($args);
    }

    /**
     * Limit query
     * @param int $limit
     * @param int $offset
     * @return DalBasicQuery
     */
    public function limit($limit, $offset = 0) {
        if ($offset) {
            return $this->query('LIMIT ? OFFSET ?', (int)$offset, (int)$limit);
        } else {
            return $this->query('LIMIT ?', (int)$limit);
        }
    }

    /**
     * Execute query
     * @param bool $last_id
     * @return DalBasicQuery
     * @throws DalException
     */
    public function exec($last_id = false) {
        $sql = $this->sql;
        $this->result = $this->connection->query($sql);
        $this->sql = '';
        $this->classname = null;
        if (!$this->result) {
            throw new DalException(sprintf("MySQL ERROR: %s, SQL: %s", $this->connection->error, $sql),
                $this->connection->errno);
        }
        return $last_id ? $this->lastId() : $this->result;
    }

    /**
     * Get last inserted id
     * @return mixed
     */
    public function lastId() {
        return $this->connection->insert_id;
    }

    /**
     * Get affected rows
     * @return int
     */
    public function affectedRows() {
        return $this->connection->affected_rows;
    }

    /**
     * Fetch single value from database
     * @return mixed
     */
    public function fetchCell() {
        $this->exec();
        $row = $this->result->fetch_row();
        return $row[0];
    }

    /**
     * Fetch row as object
     * @param string $class Result type
     * @return object
     */
    public function fetchObject($class = null) {
        if (!$class) $class = $this->classname;
        $this->exec();
        $row = $class ? $this->result->fetch_object($class)
            : $this->result->fetch_object();
        return $row;
    }

    /**
     * Fetch row as array
     * @return array
     */
    public function fetchArray() {
        $this->exec();
        $row = $this->result->fetch_row();
        return $row;
    }

    /**
     * Fetch row as associative array
     * @return array
     */
    public function fetchAssoc() {
        $this->exec();
        $row = $this->result->fetch_assoc();
        return $row;
    }

    /**
     * Fetch all rows as array of objects
     * @param string $class Result type
     * @return array
     */
    public function fetchAllObject($class = null) {
        if (!$class) $class = $this->classname;
        $this->exec();
        $res = array();
        while ($row = $class ? $this->result->fetch_object($class)
            : $this->result->fetch_object()) {
            $res []= $row;
        }
        return $res;
    }

    /**
     * Fetch all rows as array of arrays
     * @return array
     */
    public function fetchAllArray() {
        $this->exec();
        $res = $this->result->fetch_all(MYSQLI_NUM);
        return $res;
    }

    /**
     * Fetch all rows as array of associative arrays
     * @return array
     */
    public function fetchAllAssoc() {
        $this->exec();
        $res = $this->result->fetch_all(MYSQLI_ASSOC);
        return $res;
    }

    // Useless methods

    /**
     * Fetch row as object from database
     * @param string $table
     * @param string $field Field in where clause
     * @param string $value Value in where clause
     * @param string $class Result type
     * @return object
     */
    public function getObject($table, $field, $value, $class = null) {
        $this->select()->from($table)->where("$field = ?", $value);
        return $this->fetchObject($class);
    }

    /**
     * Fetch row as array from database
     * @param string $table
     * @param string $field Field in where clause
     * @param string $value Value in where clause
     * @return array
     */
    public function getArray($table, $field, $value) {
        $this->select()->from($table)->where("$field = ?", $value);
        return $this->fetchArray();
    }

    /**
     * Fetch row as associative array from database
     * @param string $table
     * @param string $field Field in where clause
     * @param string $value Value in where clause
     * @return array
     */
    public function getAssoc($table, $field, $value) {
        $this->select()->from($table)->where("$field = ?", $value);
        return $this->fetchAssoc();
    }

}
