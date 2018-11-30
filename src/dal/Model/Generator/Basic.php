<?php

namespace Dal\Model\Generator;

/**
 * Parent of model generators
 */
class Basic
{

    var $profile;
    var $config;
    var $rootPath;

    function __construct($config, $rootPath, $profile = 'default') {
        $this->config = $config->{$profile};
        $this->rootPath = $rootPath;
        $this->profile = $profile;
    }

    function getTableClassName($tableName) {
        $parts = explode('_', $tableName);
        foreach($parts as $key => $value){
            $parts[$key] = ucfirst($value);
        }
        return join('', $parts);
    }

    function getClassName($tableName) {
        if (strlen($tableName) > 1 && $tableName[strlen($tableName) - 1] == 's') {
            $tableName = substr($tableName, 0, strlen($tableName) - 1);
        }
        $parts = explode('_', $tableName);
        foreach($parts as $key => $value){
            $parts[$key] = ucfirst($value);
        }
        return join('', $parts);
    }

    function namespaceToPath($namespace) {
        $path = explode('\\', $namespace);
        array_walk($path, function(&$a) {
            $a = strtolower($a);
        });
        return implode('/', $path);
    }

}