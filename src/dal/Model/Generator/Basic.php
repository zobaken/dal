<?php

namespace Dal\Model\Generator;
use Symfony\Component\Inflector\Inflector;

/**
 * Parent of model generators
 */
class Basic
{

    var $config;
    var $targetDir;
    var $profile;
    var $dbname;

    function __construct($targetDir, $profile = 'default', $dbname = null) {
        $this->config = \Dal\Dal::getConfiguration()->$profile;
        $this->targetDir = $targetDir;
        $this->profile = $profile;
        $this->dbname = $dbname;
    }

    function getTableClassName($tableName) {
        $parts = explode('_', $tableName);
        foreach($parts as $key => $value){
            $parts[$key] = ucfirst($value);
        }
        return join('', $parts);
    }

    function getClassName($tableName) {
        $parts = explode('_', $tableName);
        $parts[count($parts) - 1] = Inflector::singularize($parts[count($parts) - 1]);
        foreach($parts as $key => $value){
            $parts[$key] = ucfirst($value);
        }
        return join('', $parts);
    }

    function namespaceToPath($namespace) {
        $path = explode('\\', $namespace);
        return '/' . implode('/', $path);
    }

}