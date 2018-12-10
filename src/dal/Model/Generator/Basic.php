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
    var $existingModelFiles;

    function __construct($targetDir, $profile = 'default', $singularize = false) {
        $this->config = \Dal\Dal::getConfiguration()->$profile;
        $this->targetDir = $targetDir;
        $this->profile = $profile;
        $this->singularize = $singularize;
    }

    function getTableClassName($tableName) {
        $parts = explode('_', $tableName);
        foreach($parts as $key => $value){
            $parts[$key] = ucfirst($value);
        }
        return join('', $parts) . 'Prototype';
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

    function getNamespacePath($namespace) {
        $path = explode('\\', $namespace);
        return '/' . implode('/', $path);
    }

    function setClassMap($classMap) {
        $this->classmap = $classMap;
    }

    function getExistingClassFiles() {
        if (!file_exists($this->targetDir)) {
            return [];
        }
        $result = [];
        $files = glob($this->targetDir . '/*.php');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (preg_match('/extends ([\\w\\\\]+)/', $content, $m)) {
                $result[$m[1]] = $file;
            }
        }
        $this->existingModelFiles =  $result;
    }

}