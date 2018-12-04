<?php

namespace Dal\Model;

/**
 * Class GeneratorFactory
 * @package Dal\Model
 */
class GeneratorFactory
{

    /**
     * Create model generator
     * @param \stdClass $config Configuration profile
     * @return mixed
     */
    static function createGenerator($config, $rootPath, $profile, $dbname = null) {
        $className = "\\Dal\\Model\\Generator\\{$config->driver}";
        return new $className($config, $rootPath, $profile, $dbname);
    }

}