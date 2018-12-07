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

        $dbname = $this->dbname ?: $this->config->dbname;
        $tables = db($this->profile)->query("SHOW TABLES FROM #?", $dbname)->fetchAllArray();

        foreach($tables as $tc){
            $tableName = $tc[0];
            echo "Working on {$tableName}\n";
            $tableInfo = db($this->profile)->query("SHOW FULL COLUMNS FROM #?", $tableName)->fetchAllAssoc();
            $tableClassName = $this->getTableClassName($tableName);
            $className = $this->getClassName($tableName);
            $pk = [];
            $generated = [];
            foreach($tableInfo as $tableField){
                if($tableField['Key'] == 'PRI'){
                    $pk[] = sprintf("'%s'", $tableField['Field']);
                }
                if ($tableField['Comment'] == 'uid') {
                    $generated[$tableField['Field']] = 'uid';
                } elseif ($tableField['Comment'] == 'uint') {
                    $generated[$tableField['Field']] = 'uint';
                }
            }

            if (isset($this->config->namespace)) {
                $namespace = $this->config->namespace;
                $namespacePath = $this->namespaceToPath($namespace);
            } else {
                $namespace = null;
                $namespacePath = '';
            }

            $profile = $this->profile;
            ob_start();
            require DAL_PATH . '/templates/mysql/table-class.tpl';
            $tableClassContent = sprintf("<?php \n\n%s", ob_get_clean());
            $tableClassPath = $this->targetDir . "$namespacePath/Table/{$tableClassName}Prototype.php";
            $classPath = $this->targetDir . "$namespacePath/$className.php";
            if (!is_dir(dirname($tableClassPath))) {
                mkdir(dirname($tableClassPath), 0755, true);
            }
            file_put_contents($tableClassPath, $tableClassContent);
            if (!file_exists($classPath)) {
                ob_start();
                require DAL_PATH . '/templates/mysql/class.tpl';
                $classContent = sprintf("<?php \n\n%s", ob_get_clean());
                if (!is_dir(dirname($classPath))) {
                    mkdir(dirname($classPath), 0755, true);
                }
                file_put_contents($classPath, $classContent);
            }
        }

        echo "Done\n";
    }

}