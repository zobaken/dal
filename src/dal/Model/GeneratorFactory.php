<?php

namespace Dal\Model;
use \Dal\Exception;

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
        if (empty($config->$profile)) {
            throw new Exception('Profile not found');
        }
        $className = ucfirst($config->$profile->driver);
        $className = "\\Dal\\Model\\Generator\\$className";
        return new $className($config, $rootPath, $profile, $dbname);
    }

}