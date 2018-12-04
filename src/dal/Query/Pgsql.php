<?php

/** @noinspection SqlDialectInspection */

namespace Dal\Query;

/**
 * Pgsql implementation
 */
class Pgsql extends Basic {

    /**
     * @var resource Postgres connection resource
     */
    public $connection = null;

    /**
     * @var resource
     */
    public $result = null;

    /**
     * Construct query from configuration or another Dal\mysql object
     * @param object $init Configuration object (host, user, password, dbname) or Dal\mysql
     */
    public function __construct($init = null) {
        if (get_class($init) == 'Dal\Query\Pgsql') {
            $this->cfg = $init->cfg;
            $this->connection = $init->connection;
            $this->result = $init->result;
        } else {
            $this->cfg = $init;
    }
    }

    /**
     * Create new query with same connection
     * @return Pgsql
     */
    public function __invoke() {
        $copy = new Pgsql($this);
        return $copy;
    }

    /**
     * Get connection string from configuration
     */
    protected function getConnectionString() {
        $connectionArray = (array)$this->cfg;
        $connectionArray = array_filter($connectionArray, function(&$value) {
            return in_array($value, [
                'host',
                'user',
                'password',
                'dbname',
            ]);
        }, ARRAY_FILTER_USE_KEY);
        array_walk($connectionArray, function(&$value, $key) {
            $value = $key . "='" . addslashes($value) . "'";
        });
        $connectionString = implode(' ', array_values($connectionArray));
        $connectionString .= ' options=\'--client_encoding=UTF8\'';
        return $connectionString;
    }

    /**
     * Connect to database
     * @param object $cfg Configuration object (host, user, password, dbname)
     * @return \Dal\Query\Pgsql
     * @throws \Dal\Exception
     */
    public function connect($cfg = null) {
        if ($cfg) $this->cfg = $cfg;
        if ($this->connection) return $this;
        $this->connection = \pg_connect($this->getConnectionString());
        if (!$this->connection) {
            throw new \Dal\Exception('Connection failed: ' . \pg_last_error());
        }
        return $this;
    }

    /**
     * Disconnect from database
     */
    public function disconnect() {
        if ($this->connection) \pg_close($this->connection);
    }

    /**
     * Quote database name
     * @param string $name
     * @return string
     */
    public function quoteName($name) {
        if (is_array($name) || is_object($name)) {
            $names = array_map(array($this, 'quoteName'), (array)$name);
            return implode(', ', $names);
        }
        return '"' . \pg_escape_string($this->connection, $name) . '"';
    }

    /**
     * Quote database value
     * @param mixed $val
     * @return string
     * @throws \Dal\Exception
     */
    public function quote($val) {
        if ($val === null) return 'NULL';
        if ($val === false) return '0';
        if ($val === true) return '1';
        if (is_int($val)) return (string)$val;
        if (is_array($val) || is_object($val)) {
            $values = array_map(array($this, 'quote'), (array)$val);
            return implode(', ', $values);
        }
        if (!$this->connection) {
            $this->connect();
        }
        return "'" . \pg_escape_string($this->connection, $val) . "'";
    }

    /**
     * Select query
     * @param string $what
     * @return \Dal\Query\Pgsql
     */
    public function select($what = '*') {
        $args = func_get_args();
        $args[0] = 'SELECT ' . $what;
        return $this->queryArgs($args);
    }

    /**
     * Select all query
     * @return \Dal\Query\Pgsql
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
     * @return \Dal\Query\Pgsql
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
     * @param bool $returnLastId Return last inserted id
     * @return \resource|int
     * @throws \Dal\Exception
     */
    public function exec($returnLastId = false) {
        $sql = $this->sql;
        if (!$this->connection) {
            $this->connect();
        }
        $this->result = \pg_query($this->connection, $sql);

        $this->sql = '';
        $this->classname = null;
        if (!$this->result) {
            throw new \Dal\Exception(sprintf("MySQL ERROR: %s, SQL: %s", \pg_last_error($this->connection), $sql));
        }
        return $returnLastId ? $this->lastId() : $this->result;
    }

    /**
     * Get last inserted id
     * @return mixed
     */
    public function lastId() {
        return $this->query('SELECT LASTVAL()')->fetchCell();
    }

    /**
     * Get affected rows
     * @return int
     */
    public function affectedRows() {
        return \pg_affected_rows($this->result);
    }

    /**
     * Fetch single value from database
     * @return mixed
     * @throws \Dal\Exception
     */
    public function fetchCell() {
        $this->exec();
        if (\pg_num_rows($this->result)) {
            $result = \pg_fetch_result($this->result, 0, 0);
            return $result;
        }
        return null;
    }

    /**
     * Fetch row as object
     * @param string $class Result type
     * @return object|null
     * @throws \Dal\Exception
     */
    public function fetchObject($class = null) {
        if (!$class) $class = $this->classname;
        $this->exec();
        $row = $class ? \pg_fetch_object($this->result, null, $class)
            : \pg_fetch_object($this->result);
        if ($row === false) {
            $row = null;
        }
        return $row;
    }

    /**
     * Fetch row as array
     * @return array|null
     * @throws \Dal\Exception
     */
    public function fetchArray() {
        $this->exec();
        $row = \pg_fetch_row($this->result);
        if ($row === false) {
            $row = null;
        }
        return $row;
    }

    /**
     * Fetch row as associative array
     * @return array|null
     * @throws \Dal\Exception
     */
    public function fetchAssoc() {
        $this->exec();
        $row = \pg_fetch_assoc($this->result);
        if ($row === false) {
            $row = null;
        }
        return $row;
    }

    /**
     * Fetch all rows as array of objects
     * @param string $class Result type
     * @return array
     * @throws \Dal\Exception
     */
    public function fetchAllObject($class = null) {
        if (!$class) $class = $this->classname;
        $this->exec();
        $res = [];
        while ($row = ($class ? \pg_fetch_object($this->result, null, $class)
            : \pg_fetch_object($this->result))) {
            $res []= $row;
        }
        return $res;
    }

    /**
     * Fetch all rows as array of arrays
     * @return array
     * @throws \Dal\Exception
     */
    public function fetchAllArray() {
        $this->exec();
        $res = \pg_fetch_all($this->result, PGSQL_NUM);
        if ($res === false) {
            $res = [];
        }
        return $res;
    }

    /**
     * Fetch all rows as array of associative arrays
     * @return array
     * @throws \Dal\Exception
     */
    public function fetchAllAssoc() {
        $this->exec();
        $res = \pg_fetch_all($this->result, PGSQL_ASSOC);
        if ($res === false) {
            $res = [];
        }
        return $res;
    }

    /**
     * Fetch first result field from all rows as array
     * @param int|string $column Column index or name
     * @return array
     * @throws \Dal\Exception
     */
    public function fetchColumn($column = 0) {
        $this->exec();
        if (!\pg_num_rows($this->result)) return [];
        if (is_int($column)) {
            return \pg_fetch_all_columns($this->result, $column);
        }
        return array_map(function ($row) use ($column) {
            return $row[$column];
        }, $this->fetchAllAssoc());
    }

    // Useless methods

    /**
     * Fetch row as object from database
     * @param string $table
     * @param string $field Field in where clause
     * @param string $value Value in where clause
     * @param string $class Result type
     * @return object
     * @throws \Dal\Exception
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
     * @throws \Dal\Exception
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
     * @throws \Dal\Exception
     */
    public function getAssoc($table, $field, $value) {
        $this->select()->from($table)->where("$field = ?", $value);
        return $this->fetchAssoc();
    }

}
