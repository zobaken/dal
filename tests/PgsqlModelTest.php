<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/helper/PgsqlHelper.php';

use PHPUnit\Framework\TestCase;

class PgsqlModelTest extends TestCase {

    use PgsqlHelper;

    var $config;

    function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->config = json_decode(file_get_contents(__DIR__ . '/helper/config.json'));
        Dal\Dal::reset();
        Dal\Dal::setConfiguration($this->config);
        spl_autoload_register(function ($class) {
            $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
            $path = __DIR__ . '/classes/' . $class . '.php';
            if (file_exists($path)) {
                require_once $path;
            }
        });
    }

    function testGeneration() {

        if (is_dir(__DIR__ . '/classes')) {
            $this->removeDirectory(__DIR__ . '/classes');
        }

        $this->createTestTable();
        $generator = new \Dal\Model\Generator\Pgsql(__DIR__ . '/classes', 'pgsql', $this->dbname);
        $generator->run();
        $this->assertTrue(file_exists(__DIR__ . '/classes/Space/Test.php'));
        $this->assertTrue(file_exists(__DIR__ . '/classes/Space/Table/TestPrototype.php'));

        // Test functionality

        require_once __DIR__ . '/classes/Space/Test.php';

        $this->assertTrue(class_exists('\Space\Test'));

        // Insert

        $test = new \Space\Test();
        $test->name = 'test name';
        $test->created_ts = dbtime();
        $test->hash = md5('hash');
        $id = $test->insert(true);

        $this->assertNotNull($id);
        $this->assertGreaterThan(0, $id);

        $test = null;

        $test = \Space\Test::get($id);

        $this->assertAttributeEquals('test name', 'name', $test);
        $this->assertAttributeEquals(md5('hash'), 'hash', $test);
        $this->assertAttributeEquals($id, 'id', $test);

        // Update

        $test->hash = md5('hash2');
        $affected = $test->update();

        $this->assertEquals(1, $affected);

        $test = null;

        $test = \Space\Test::get($id);

        $this->assertAttributeEquals(md5('hash2'), 'hash', $test);

        // Delete

        $test->remove();
        $test = \Space\Test::get($id);

        $this->assertNull($test);
    }

}