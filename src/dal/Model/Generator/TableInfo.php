<?php

namespace Dal\Model\Generator;

use Symfony\Component\Inflector\Inflector;

/**
 * Class to work on table
 */
class TableInfo {

    /**
     * @var \Dal\Model\Generator\Basic
     */
    var $generator;
    var $name;
    var $className;
    var $tableClassName;
    var $classNamespace;
    var $tableClassNamespace;
    var $classPath;
    var $tableClassPath;

    public function __construct($generator, $tableName) {
        $this->generator = $generator;
        $this->name = $tableName;
        $this->init();
    }

    public function init() {
        $this->tableClassName = $this->getTableClassName();
        $this->className = $this->getClassName();
        $this->classNamespace = $this->getClassNamespace();
        $this->tableClassNamespace = $this->getTableClassNamespace();
        $this->classPath = $this->getClassPath();
        $this->tableClassPath = $this->getTableClassPath();
    }

    function getTableClassName() {
        $parts = explode('_', $this->name);
        foreach($parts as $key => $value){
            $parts[$key] = ucfirst($value);
        }
        return join('', $parts) . 'Prototype';
    }

    function getClassName() {
        if (isset($this->generator->classmap[$this->name])) {
            return $this->generator->classmap[$this->name];
        }
        $parts = explode('_', $this->name);
        if ($this->generator->singularize && !preg_match('/\\d/', $parts[count($parts) - 1])) {
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

    function getCLassNamespace() {
        return isset($this->generator->config->namespace) ? $this->generator->config->namespace: '';
    }

    function getTableClassNamespace() {
        if ($this->classNamespace) {
            return "{$this->classNamespace}\\Table";
        }
        return 'Table';
    }

    function getClassPath() {
        if ($this->classNamespace) {
            return $this->generator->targetDir . '/' . str_replace('\\', '/', $this->classNamespace)
                . '/' . $this->className . '.php';
        }
        return $this->generator->targetDir . '/' . $this->className . '.php';
    }

    function getTableClassPath() {
        return $this->generator->targetDir . '/' . str_replace('\\', '/', $this->tableClassNamespace)
            . '/' . $this->tableClassName . '.php';
    }

    function writeFiles($tableClassContent, $classContent) {
        if (!is_dir(dirname($this->tableClassPath))) {
            mkdir(dirname($this->tableClassPath), 0755, true);
        }
        file_put_contents($this->tableClassPath, $tableClassContent);
        $tableFullClassName = "{$this->tableClassNamespace}\\{$this->tableClassName}";
        if (!file_exists($this->classPath)
                && !isset($this->generator->existingModelFiles[$tableFullClassName])) {
            if (!is_dir(dirname($this->classPath))) {
                mkdir(dirname($this->classPath), 0755, true);
            }
            file_put_contents($this->classPath, $classContent);
        }
    }

}