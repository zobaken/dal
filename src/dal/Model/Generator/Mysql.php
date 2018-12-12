<?php

namespace Dal\Model\Generator;

/**
 * Mysql model classes generator
 */
class Mysql extends Basic {

    /**
     * Generate mysql classes
     *
     * @throws \Dal\Exception
     */
    function run() {

        $tables = db($this->profile)->query("SHOW TABLES FROM #?", $this->config->dbname)->fetchAllArray();
        $this->searchExistingClassFiles();

        foreach($tables as $tc){
            $tableName = $tc[0];
            echo "Working on {$tableName}\n";
            $tableInfo = new TableInfo($this, $tableName);
            $tableColumns = db($this->profile)->query("SHOW FULL COLUMNS FROM #?", $tableName)->fetchAllAssoc();
            $tableClassName = $tableInfo->tableClassName;
            $className = $tableInfo->className;
            $pk = [];
            $generated = [];
            foreach($tableColumns as $tableField){
                if($tableField['Key'] == 'PRI'){
                    $pk[] = sprintf("'%s'", $tableField['Field']);
                }
                if ($tableField['Comment'] == 'uid') {
                    $generated[$tableField['Field']] = 'uid';
                } elseif ($tableField['Comment'] == 'uint') {
                    $generated[$tableField['Field']] = 'uint';
                }
            }

            $namespace = $tableInfo->classNamespace;
            $tableNamespace = $tableInfo->tableClassNamespace;
            $profile = $this->profile;
            ob_start();
            require DAL_PATH . '/templates/mysql/table-class.tpl';
            $tableClassContent = sprintf("<?php \n\n%s", ob_get_clean());
            ob_start();
            require DAL_PATH . '/templates/mysql/class.tpl';
            $classContent = sprintf("<?php \n\n%s", ob_get_clean());
            $tableInfo->writeFiles($tableClassContent, $classContent);
        }

        echo "Done\n";
    }

}