<?php

namespace Dal\Model\Generator;

/**
 * Pgsql model classes generator
 */
class Pgsql extends Basic
{

    /**
     * Generate mysql classes
     *
     * @throws \Dal\Exception
     */
    function run()
    {

        $this->searchExistingClassFiles();

        $tables = db($this->profile)->query("SELECT table_schema,table_name
            FROM information_schema.tables
            WHERE table_schema = ?
            ORDER BY table_schema, table_name", 'public'
        )->fetchAllAssoc();

        foreach ($tables as $tc) {
            $tableName = $tc['table_name'];
            echo "Working on {$tableName}\n";
            $tableInfo = new TableInfo($this, $tableName);
            $tableColumns = db($this->profile)->query("SELECT *
                FROM information_schema.columns
                WHERE table_schema = ?
                AND table_name  = ?", 'public', $tableName)->fetchAllAssoc();

            $tableConstraints = db($this->profile)->query("SELECT *
                FROM information_schema.table_constraints
                WHERE table_schema = ?
                AND table_name = ?
                ORDER BY table_schema, table_name", 'public', $tableName
            )->fetchAllAssoc();

            $tableConstraints = associate($tableConstraints, 'constraint_name');

            $tableConstraintsUsage = db($this->profile)->query("SELECT *
                FROM information_schema.constraint_column_usage
                WHERE table_schema = ?
                AND table_name = ?
                ORDER BY table_schema, table_name", 'public', $tableName
            )->fetchAllAssoc();

            $tableClassName = $tableInfo->tableClassName;
            $className = $tableInfo->className;
            $pk = [];
            $generated = [];
            $sequences = [];
            foreach ($tableColumns as $tableColumn) {

                // Find primary key
                foreach ($tableConstraintsUsage as $usage) {
                    if ($usage['column_name'] == $tableColumn['column_name']) {
                        $constraint = $tableConstraints[$usage['constraint_name']];
                        if ($constraint['constraint_type'] == 'PRIMARY KEY') {
                            $pk[] = sprintf("'%s'", $tableColumn['column_name']);
                        }
                    }
                }

                // Find sequences
                if ($tableColumn['column_default'] && substr($tableColumn['column_default'], 0, 7) == 'nextval') {
                    $sequences []= "'{$tableColumn['column_name']}'";
                }

            }

            $sequences = implode(', ', $sequences);
            $profile = $this->profile;
            $namespace = $tableInfo->classNamespace;
            $tableNamespace = $tableInfo->tableClassNamespace;
            ob_start();
            require DAL_PATH . '/templates/pgsql/table-class.tpl';
            $tableClassContent = sprintf("<?php \n\n%s", ob_get_clean());
            ob_start();
            require DAL_PATH . '/templates/pgsql/class.tpl';
            $classContent = sprintf("<?php \n\n%s", ob_get_clean());
            $tableInfo->writeFiles($tableClassContent, $classContent);
        }

        echo "Done\n";
    }

}