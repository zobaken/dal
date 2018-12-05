<?php

require_once __DIR__ . '/helper/MysqlHelper.php';

use PHPUnit\Framework\TestCase;

class MysqlModelTest extends TestCase {

    use MysqlHelper;

    var $config;

    function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->config = json_decode(file_get_contents(__DIR__ . '/helper/config.json'));
        Dal\Dal::reset();
        Dal\Dal::setConfiguration($this->config);
    }

    function testGeneration() {

        // Test create classes
        if (is_dir(DAL_PATH . '/classes')) {
            $this->removeDirectory(DAL_PATH . '/classes');
        }
        $this->createDatabase();
        $this->createTestTable();
        $generator = new \Dal\Model\Generator\Mysql($this->config, DAL_PATH . '/classes', 'default', $this->dbname);
        $generator->run();
        $this->assertTrue(file_exists(DAL_PATH . '/classes/Test.php'));
        $this->assertTrue(file_exists(DAL_PATH . '/classes/Table/TestPrototype.php'));

        // Test functionality

        require_once DAL_PATH . '/classes/Test.php';

        $this->assertTrue(class_exists('Test'));

        // Insert

        $test = new Test();
        $test->name = 'test name';
        $test->created_ts = dbtime();
        $test->hash = md5('hash');
        $id = $test->insert(true);

        $this->assertNotNull($id);
        $this->assertGreaterThan(0, $id);

        $test = null;

        $test = Test::get($id);

        $this->assertAttributeEquals('test name', 'name', $test);
        $this->assertAttributeEquals(md5('hash'), 'hash', $test);
        $this->assertAttributeEquals($id, 'id', $test);

        // Update

        $test->hash = md5('hash2');
        $affected = $test->update();

        $this->assertEquals(1, $affected);

        $test = null;

        $test = Test::get($id);

        $this->assertAttributeEquals(md5('hash2'), 'hash', $test);

        // Delete

        $test->remove();
        $test = Test::get($id);

        $this->assertNull($test);
    }

}