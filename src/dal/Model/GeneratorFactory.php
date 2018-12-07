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
     * @param string $targetDir Target directory
     * @param string $profile Database profile
     * @param string $dbname Override database name
     * @return mixed
     * @throws \Dal\Exception
     */
    static function createGenerator($targetDir, $profile = 'default', $dbname = null) {
        $config = \Dal\Dal::getConfiguration();
        if (empty($config->$profile)) {
            throw new Exception('Profile not found');
        }
        $className = ucfirst($config->$profile->driver);
        $className = "\\Dal\\Model\\Generator\\$className";
        return new $className($targetDir, $profile, $dbname);
    }

}