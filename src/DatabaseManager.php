<?php

declare(strict_types=1);

namespace CoStack\MysqlLoader;

use Doctrine\DBAL\Connection;

class DatabaseManager
{
    public function getDatabaseInformation(Connection $connection, DumpConfiguration $dumpConfiguration): DatabaseInfo
    {
        $tables = $connection->getSchemaManager()->listTables();

        $namedTables = $tableNames = $emptyTables = $excludedTables = [];

        foreach ($tables as $table) {
            $tableNames[] = $tableName = $table->getName();
            $namedTables[$tableName] = Table::fromTable($table);

            $query = $connection->createQueryBuilder();
            $query->select('count(*) AS CNT')->from($tableName);
            $count = $query->executeQuery()->fetchOne();
            if ($count === 0) {
                $emptyTables[] = $tableName;
            }
            if ($dumpConfiguration->isExcluded($tableName)) {
                $excludedTables[] = $tableName;
            }
        }

        return new DatabaseInfo($namedTables, $tableNames, $emptyTables, $excludedTables);
    }
}
