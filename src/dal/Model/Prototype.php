<?php

namespace Dal\Model;

/**
 * Parent class for all database classes
 */
class Prototype {

    /** @var  string Table name */
    static $table;

    /** @var array Primary key */
    static $pk;

    /** @var string Configuration profile */
    static $profile;

    /** @var  array Sequence fields */
    static $sequences;

    /** @var  array Generated fields */
    static $generated;

    /**
     * Insert object into database
     * @param $returnLastId bool Return last autogenerated id
     * @return mixed
     * @throws \Dal\Exception
     */
    function insert($returnLastId = false) {
        $this->generateFields();
        return static::queryInsertRow((array)$this)->exec($returnLastId);
    }

    /**
     * Update object in database
     * @return int Affected rows
     * @throws \Dal\Exception
     */
    function update() {
        $q = static::queryUpdateRow((array)$this);
        static::generateWhere($q, $this->getId());
        $q->exec();
        return $q->affectedRows();
    }

    /**
     * Replace object in database
     * @throws \Dal\Exception
     */
    function replace() {
        static::queryReplaceRow((array)$this)->exec();
    }

    /**
     * Remove object from database
     * @return bool Successful
     * @throws \Dal\Exception
     */
    function remove() {
        static::delete($this->getId());
        return (bool)\Dal\Dal::getQuery(static::$profile)->affectedRows();
    }

    /**
     * Init object from post request
     * @param array $ignore array of parameters to skip
     */
    function initPost($ignore = null) {
        if ($ignore) $ignore = (array)$ignore;
        foreach ($this as $k => $v) {
            if ($ignore && array_search($k, $ignore) !== false || strpos($k, '_') === 0)
                continue;
            if (isset($_POST[$k])) {
                $val = trim($_POST[$k]);
                $this->{$k} = $val === '' ? null : $val;
            }
        }
    }

    /**
     * Get id for object
     * @return string|array
     */
    function getId() {
        $pk = static::$pk;
        if (count($pk) == 1) return $this->{$pk[0]};
        $key = [];
        foreach ($pk as $field) {
            $key []= $this->{$field};
        }
        return $key;
    }

    /**
     * Generate fields
     */
    function generateFields() {
        foreach (static::$generated as $field=>$method) {
            if ($method == 'uint' && !$this->$field) {
                $this->$field = uint();
                continue;
            }
            if ($method == 'uid' && !$this->$field) {
                $this->$field = uid();
            }
        }
    }

    /**
     * Initialize object from parameters
     * @param array $params
     */
    function initObject($params) {
        foreach ((array)$params as $k=>$v) {
            if ($k != static::$pk && property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
    }

    /**
     * Get object from database
     * @param mixed $id
     * @return Prototype|\stdClass
     * @throws \Dal\Exception
     */
    static function get($id) {
        if (is_array($id)) $key = $id;
        else $key = func_get_args();
        $db = static::querySelect();
        static::generateWhere($db, $key);
        return $db->fetchRow(get_called_class());
    }

    /**
     * Get all objects from database
     * @param string $sort_field
     * @return array
     * @throws \Dal\Exception
     */
    static function getAll($sort_field = null) {
        return static::querySelect()
            ->ifQ($sort_field, "ORDER BY $sort_field")
            ->fetchAll(get_called_class());
    }

    /**
     * Find objects
     * @param string $where Where clause. For example A::find('id = ?', $id)
     * @return array
     * @throws \Dal\Exception
     */
    static function find($where)
    {
        $q = static::querySelect();
        return call_user_func_array([$q, 'where'], func_get_args())
            ->fetchAll(get_called_class());
    }

    /**
     * Find single object
     * @param string $where Where clause. For example A::findRow('id = ?', $id)
     * @return object
     * @throws \Dal\Exception
     */
    static function findRow($where)
    {
        $q = static::querySelect();
        return call_user_func_array([$q, 'where'], func_get_args())
            ->fetchRow(get_called_class());
    }

    /**
     * Delete row by id
     * @param mixed $key
     * @return integer, asffected rows
     * @throws \Dal\Exception
     */
    static function delete($key) {
        $key = is_array($key) ? $key : func_get_args();
        $db = static::queryDelete();
        static::generateWhere($db, $key);
        $db->exec();
        return $db->affectedRows();
    }

    /**
     * Get query for our object
     * @return \Dal\Query\Basic
     * @throws \Dal\Exception
     */
    function query() {
        return \Dal\Dal::getQuery(static::$profile);
    }

    /**
     * Return \Dal\Query\Basic select query
     * @param string $what Columns to select
     * @return \Dal\Query\Basic
     * @throws \Dal\Exception
     */
    static function querySelect($what = '*') {
        $table = static::$table;
        return \Dal\Dal::getQuery(static::$profile)
            ->setClass(get_called_class())
            ->select($what)
            ->from($table);
    }

    /**
     * Return \Dal\Query\Basic delete query
     * @return \Dal\Query\Basic
     * @throws \Dal\Exception
     */
    static function queryDelete() {
        return \Dal\Dal::getQuery(static::$profile)->deleteFrom(static::$table);
    }

    /**
     * Return \Dal\Query\Basic update query
     * @return \Dal\Query\Basic
     * @throws \Dal\Exception
     */
    static function queryUpdate() {
        return  \Dal\Dal::getQuery(static::$profile)->update(static::$table);
    }

    /**
     * Return \Dal\Query\Basic replace
     * @return \Dal\Query\Basic
     * @throws \Dal\Exception
     */
    static function queryReplace() {
        return \Dal\Dal::getQuery(static::$profile)->replace(static::$table);
    }

    /**
     * Return \Dal\Query\Basic insert query
     * @return \Dal\Query\Basic
     * @throws \Dal\Exception
     */
    static function queryInsert() {
        return  \Dal\Dal::getQuery(static::$profile)->insertInto(static::$table);
    }

    /**
     * Return \Dal\Query\Basic update query with values assignment
     * @param array $fields affected fields
     * @return \Dal\Query\Basic
     * @throws \Dal\Exception
     */
    static function queryUpdateRow($fields) {
        $db = \Dal\Dal::getQuery(static::$profile)->update(static::$table);
        $q = [];
        foreach($fields as $k=>$v) {
            $q []= $db->quoteName($k) . '=' . $db->quote($v);
        }
        return $db->set(implode(',', $q));
    }

    /**
     * Return \Dal\Query\Basic replace query with values assignment
     * @param array $fields affected fields
     * @return \Dal\Query\Basic
     * @throws \Dal\Exception
     */
    static function queryReplaceRow($fields) {
        $db = \Dal\Dal::getQuery(static::$profile)->replace(static::$table);
        $q = [];
        foreach($fields as $k=>$v) {
            $q []= $db->quoteName($k) . '=' . $db->quote($v);
        }
        return $db->set(implode(',', $q));
    }

    /**
     * Return \Dal\Query\Basic insert query
     * @param array $fields affected fields
     * @return \Dal\Query\Basic
     * @throws \Dal\Exception
     */
    static function queryInsertRow($fields) {
        if (static::$sequences) {
            foreach (static::$sequences as $seq) {
                if (array_key_exists($seq, $fields) && is_null($fields[$seq])) {
                    unset($fields[$seq]);
                }
            }
        }
        $db = \Dal\Dal::getQuery(static::$profile)->insertInto(static::$table);
        return $db->query('(#?) VALUES (?)', array_keys($fields), array_values($fields));
    }

    /**
     * Generate where statement
     * @param \Dal\Query\Basic $db
     * @param array|string $key Values for primary key
     * @return \Dal\Query\Basic
     */
    static function generateWhere($db, $key) {
        $key = (array)$key;
        if (count($key) == 1 && is_array($key[0]))
            $key = $key[0];
        if (is_array(static::$pk)) {
            $db->where('true');
            foreach (static::$pk as $i=>$pkname) {
                $db->q('AND #? = ?', $pkname, $key[$i]);
            }
        } else {
            $db->where('#? = ?', static::$pk, $key[0]);
        }
        return $db;
    }
}
