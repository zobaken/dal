<?php

trait PgsqlHelper
{
    
    var $dbname  = 'test';
    var $config;

    /**
     * @throws \Dal\Exception
     */
    function createDatabase() {
        $databaseExists = db()->q('SELECT 1 AS result FROM pg_database WHERE datname = ?', $this->dbname)->fetchCell();
        if ($databaseExists) {
            db()->dropDatabase($this->dbname)->exec();
        }
        db()->createDatabase($this->dbname)->exec();
    }

    /**
     * @return bool
     * @throws \Dal\Exception
     */
    function createTestTable() {

        $exists = db()->query(
            <<<SQL

SELECT 1
   FROM   information_schema.tables 
   WHERE  table_schema = 'public'
   AND    table_name = 'test'

SQL
        )->fetchCell();

        if ($exists) {
            db()->q('DROP TABLE test')->exec();
        }
        return (bool)db()->query(
            <<<SQL

CREATE TABLE test (
  id SERIAL PRIMARY KEY,
  name varchar(128) NOT NULL,
  created_ts TIMESTAMP NOT NULL,
  hash varchar(128)
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