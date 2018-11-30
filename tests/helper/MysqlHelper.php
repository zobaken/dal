<?php

trait MysqlHelper
{

    var $config;

    function createDatabase() {
        $databaseExists = db()->q('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', $this->config->default->dbname)->fetchCell();
        if ($databaseExists) {
            db()->dropDatabase($this->config->default->dbname)->exec();
        }
        db()->createDatabase($this->config->default->dbname)->exec();
        db()->q('USE #?', $this->config->default->dbname)->exec();
    }

    function createTestTable() {
        return db()->query(
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