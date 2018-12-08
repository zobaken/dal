<?php

trait MysqlHelper
{
    
    var $dbname  = 'test';
    var $config;

    /**
     * @throws \Dal\Exception
     */
    function createDatabase() {
        $databaseExists = db()->q('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', $this->dbname)->fetchCell();
        if ($databaseExists) {
            db()->dropDatabase($this->dbname)->exec();
        }
        db()->createDatabase($this->dbname)->exec();
        db()->q('USE #?', $this->dbname)->exec();
    }

    /**
     * @return mixed
     * @throws \Dal\Exception
     */
    function createTestTable() {
        db()->q('USE #?', $this->dbname)->exec();
        db()->query(
            <<<SQL

CREATE TABLE test (
  id INT NOT NULL AUTO_INCREMENT,
  name varchar(128) NOT NULL,
  created_ts DATETIME NOT NULL,
  hash varchar(128),
  PRIMARY KEY(id)
)

SQL
        )->exec();

        db()->query(
            <<<SQL

CREATE TABLE exchanges (
  id INT NOT NULL AUTO_INCREMENT,
  PRIMARY KEY(id)
)

SQL
        )->exec();

        db()->query(
            <<<SQL

CREATE TABLE d2s_i2s (
  id INT NOT NULL AUTO_INCREMENT,
  PRIMARY KEY(id)
)

SQL
        )->exec();

        db()->query(
            <<<SQL

CREATE TABLE change_names (
  id INT NOT NULL AUTO_INCREMENT,
  PRIMARY KEY(id)
)

SQL
        )->exec();
    }

    function removeDirectory($path) {
        $files = glob($path . '/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
        return;
    }

}