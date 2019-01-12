<?php

require_once  __DIR__ . '/helper/MysqlHelper.php';

use PHPUnit\Framework\TestCase;

class MysqlDbTest extends TestCase {

    use MysqlHelper;

    function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->config = json_decode(file_get_contents(__DIR__ . '/helper/config.json'));
        Dal\Dal::reset();
        Dal\Dal::setConfiguration($this->config);
    }

    function testConnection() {
        // We need to do this stupid thing for creating database if its not exists
        $this->config = json_decode(file_get_contents(__DIR__ . '/helper/config_mysql_empty.json'));
        Dal\Dal::reset();
        Dal\Dal::setConfiguration($this->config);

        Dal\Dal::setDefaultProfile('default');
        $databaseExists = db()->q('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', $this->dbname)->fetchCell();
        if ($databaseExists) {
            db()->dropDatabase($this->dbname)->exec();
        }
        $this->assertTrue(db()->createDatabase($this->dbname)->exec());
        $this->assertTrue(db()->q('USE #?', $this->dbname)->exec());

        $this->createTestTable();

        // And this stupid thing because constructor called for every method before actually run them in test suite
        $this->config = json_decode(file_get_contents(__DIR__ . '/helper/config.json'));
        Dal\Dal::reset();
        Dal\Dal::setConfiguration($this->config);
    }

    function testInsertSelect() {
        $row = [
            'name' => 'test1',
            'created_ts' => dbtime(),
            'hash' => password_hash('password', PASSWORD_DEFAULT),
        ];

        $id = db()->insertRow('test', $row)
            ->exec(true);
        $this->assertEquals(db()->affectedRows(), 1);
        $this->assertTrue(is_numeric($id) && $id > 0);

        $row['id'] = db()->lastId();

        $result = db()->selectFrom('test')
            ->where('id = ?', $row['id'])
            ->fetchAssoc();
        $this->assertTrue(password_verify('password', $result['hash']));
        unset($row['hash']);
        unset($result['hash']);
        $this->assertEquals($row, $result);
    }

    function testUpdate() {
        $this->assertTrue(db()->update('test')
            ->set('name = ?', 'new name')
            ->exec());
        $this->assertEquals(db()->affectedRows(), 1);
        $newName = db()->select('name')->from('test')
            ->where('id = ?', 1)
            ->fetchCell();
        $this->assertEquals($newName, 'new name');
    }

    function testDelete() {
        $this->assertTrue(db()->deleteFrom('test')
            ->where('id = ?', 1)
            ->exec());
        $this->assertEquals(db()->affectedRows(), 1);
        $this->assertNull(db()->select('name')->from('test')
            ->where('id = ?', 1)
            ->fetchArray()
        );
    }

}