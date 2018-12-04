<?php

use PHPUnit\Framework\TestCase;

class DalTest extends TestCase {

    var $config;

    function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->config = json_decode(file_get_contents(__DIR__ . '/helper/config.json'));
        \Dal\Dal::reset();
        \Dal\Dal::setConfiguration($this->config);
    }

    function testCreatesMysqlQuery() {
        $this->assertInstanceOf(
            Dal\Query\Mysql::class,
            \Dal\Dal::getQuery()
        );
    }

    function testCreatesPgsqlQuery() {
        $this->assertInstanceOf(
            Dal\Query\Pgsql::class,
            \Dal\Dal::getQuery('pgsql')
        );
    }
}