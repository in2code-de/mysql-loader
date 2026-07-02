<?php

declare(strict_types=1);

namespace CoStack\MysqlLoader;

use Doctrine\DBAL\Connection;

use function is_callable;

class DatabaseManager
{
    public function getDatabaseInformation(Connection $connection, DumpConfiguration $dumpConfiguration): DatabaseInfo
    {
        if (is_callable([$connection, 'createSchemaManager'])) {
            // Doctrine 4.x
            $tables = $connection->createSchemaManager()->listTables();
        } else {
            // Doctrine 3.x
            $tables = $connection->getSchemaManager()->listTableNames();
        }
        $namedTables = $tableNames = $emptyTables = $excludedTables = [];

        foreach ($tables as $table) {
            $tableName = $table->getName();
            if (!$dumpConfiguration->isIncluded($tableName)) {
                continue;
            }
            $tableNames[] = $tableName;
            $namedTables[$tableName] = Table::fromTable($table);

            $query = $connection->createQueryBuilder();
            $query->select('count(*) AS CNT')->from($tableName);
            $count = $query->executeQuery()->fetchOne();
            if ((int)$count === 0) {
                $emptyTables[] = $tableName;
            }
            if ($dumpConfiguration->isExcluded($tableName)) {
                $excludedTables[] = $tableName;
            }
        }

        return new DatabaseInfo($namedTables, $tableNames, $emptyTables, $excludedTables);
    }
}
