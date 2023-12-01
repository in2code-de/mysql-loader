<?php

declare(strict_types=1);

namespace CoStack\MysqlLoader;

use function in_array;

class DatabaseInfo
{
    public readonly array $nonEmptyNonExcludedTableNames;

    /**
     * @param array<Table> $tables
     * @param array<string> $tableNames
     * @param array<string> $emptyTables
     * @param array<string> $excludedTables
     */
    public function __construct(
        public readonly array $tables,
        public readonly array $tableNames,
        public readonly array $emptyTables,
        public readonly array $excludedTables,
    ) {
        $nonEmptyNonExcludedTableNames = [];
        foreach ($this->tableNames as $tableName) {
            if (!$this->isEmptyTable($tableName) && !$this->isExcludedTable($tableName)) {
                $nonEmptyNonExcludedTableNames[] = $tableName;
            }
        }
        $this->nonEmptyNonExcludedTableNames = $nonEmptyNonExcludedTableNames;
    }

    public function isEmptyTable(string|Table $table): bool
    {
        if ($table instanceof Table) {
            $table = $table->getName();
        }
        return in_array($table, $this->emptyTables, true);
    }

    public function isExcludedTable(string|Table $table): bool
    {
        if ($table instanceof Table) {
            $table = $table->getName();
        }
        return in_array($table, $this->excludedTables, true);
    }
}
