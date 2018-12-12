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

    /**
     * Basic constructor.
     * @param string $targetDir Where to place files
     * @param string $profile Configuration profile
     * @param bool $singularCLassNames Use singular class names
     */
    function __construct(string $targetDir, $profile = 'default', $singularCLassNames = false) {
        $this->config = \Dal\Dal::getConfiguration()->$profile;
        $this->targetDir = $targetDir;
        $this->profile = $profile;
        $this->singularize = $singularCLassNames;
    }

    /**
     * @param array $classMap Set custom class names for specified tables [ tableName => className ]
     */
    function setClassMap(array $classMap) {
        $this->classmap = $classMap;
    }

    /**
     * Search for existing models in target directory
     * @return array
     */
    function searchExistingClassFiles() {
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