<?php

require_once  __DIR__ . '/helper/PgsqlHelper.php';

use PHPUnit\Framework\TestCase;

class PgsqlDbTest extends TestCase {

    use PgsqlHelper;

    function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->config = json_decode(file_get_contents(__DIR__ . '/helper/config.json'));
        Dal\Dal::reset();
        Dal\Dal::setConfiguration($this->config);
    }

    /**
     * @throws Exception
     */
    function testCreateTable() {
        Dal\Dal::setDefaultProfile('pgsql');
        $this->assertTrue($this->createTestTable());
    }

    function testBasic() {

        $timestamp = dbtime();

        // Insert
        $row = [
            'name' => 'test1',
            'created_ts' => $timestamp,
            'hash' => password_hash('password', PASSWORD_DEFAULT),
        ];

        $id = db()->insertInto('test')
            ->q('(#?) VALUES (?)', array_keys($row), array_values($row))
            ->exec(true);
        $this->assertEquals(db()->affectedRows(), 1);
        $this->assertTrue(is_numeric($id) && $id > 0);

        // Select

        $row = [
            'id' => db()->lastId(),
            'name' => 'test1',
            'created_ts' => $timestamp,
            'hash' => password_hash('password', PASSWORD_DEFAULT),
        ];

        $result = db()->selectFrom('test')
            ->where('id = ?', $row['id'])
            ->fetchAssoc();
        $this->assertTrue(password_verify('password', $result['hash']));
        unset($row['hash']);
        unset($result['hash']);
        $this->assertEquals($row, $result);

        // Update

        $this->assertNotEmpty(db()->update('test')
            ->set('name = ?', 'new name')
            ->exec());
        $this->assertEquals(db()->affectedRows(), 1);
        $newName = db()->select('name')->from('test')
            ->where('id = ?', 1)
            ->fetchCell();
        $this->assertEquals($newName, 'new name');

        // Delete / fetchColumn

        $this->assertNotEmpty(db()->deleteFrom('test')
            ->where('id = ?', 1)
            ->exec());
        $this->assertEquals(db()->affectedRows(), 1);
        $this->assertEquals([ '0' ], db()->select(' COUNT(*)')->from('test')
            ->where('id = ?', 1)
            ->fetchColumn()
        );
    }

}