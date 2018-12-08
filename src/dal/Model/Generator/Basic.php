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
    var $classmap = [];
    var $singularize = false;

    function __construct($targetDir, $profile = 'default', $singularize = false) {
        $this->config = \Dal\Dal::getConfiguration()->$profile;
        $this->targetDir = $targetDir;
        $this->profile = $profile;
        $this->singularize = $singularize;
    }

    function getTableClassName($tableName) {
        return $this->getClassName($tableName) . 'Prototype';
    }

    function getClassName($tableName) {
        if (isset($this->classmap[$tableName])) {
            return $this->classmap[$tableName];
        }
        $parts = explode('_', $tableName);
        if ($this->singularize && !preg_match('/\\d/', $parts[count($parts) - 1])) {
            $single = Inflector::singularize($parts[count($parts) - 1]);
            if (is_array($single)) {
                $single = $single[count($single) - 1];
            }
            $parts[count($parts) - 1] = $single;
        }
        foreach($parts as $key => $value){
            $parts[$key] = ucfirst($value);
        }
        return join('', $parts);
    }

    function namespaceToPath($namespace) {
        $path = explode('\\', $namespace);
        return '/' . implode('/', $path);
    }

    function setClassMap($classMap) {
        $this->classmap = $classMap;
    }

}