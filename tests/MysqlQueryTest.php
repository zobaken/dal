<?php

require_once  __DIR__ . '/helper/MysqlHelper.php';

use PHPUnit\Framework\TestCase;

class MysqlQueryTest extends TestCase {

    use MysqlHelper;

    function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->config = json_decode(file_get_contents(__DIR__ . '/helper/config.json'));
        Dal\Dal::reset();
        Dal\Dal::setConfiguration($this->config);
    }

    function testConnection() {
        $databaseExists = db()->q('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', $this->config->default->dbname)->fetchCell();
        if ($databaseExists) {
            db()->dropDatabase($this->config->default->dbname)->exec();
        }
        $this->assertTrue(db()->createDatabase($this->config->default->dbname)->exec());
        $this->assertTrue(db()->q('USE #?', $this->config->default->dbname)->exec());
    }

    function testCreateTable() {
        $this->assertTrue($this->createTestTable());
    }

    function testInsert() {
        $row = [
            'name' => 'test1',
            'created_ts' => date('Y-m-d H:i:s'),
            'hash' => password_hash('password', PASSWORD_DEFAULT),
        ];

        $id = db()->insertInto('test')
            ->q('(#?) VALUES (?)', array_keys($row), array_values($row))
            ->exec(true);
        $this->assertEquals(db()->affectedRows(), 1);
        $this->assertTrue(is_numeric($id) && $id > 0);
    }

    function testSelect() {
        $row = [
            'id' => db()->lastId(),
            'name' => 'test1',
            'created_ts' => date('Y-m-d H:i:s'),
            'hash' => password_hash('password', PASSWORD_DEFAULT),
        ];

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