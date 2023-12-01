<?php

declare(strict_types=1);

namespace CoStack\MysqlLoader;

use CoStack\MysqlLoader\Hacks\MysqlOrderedCreateTableSql;
use Doctrine\DBAL\DriverManager;

use ZipArchive;

use function basename;
use function CoStack\Lib\mkdir_deep;
use function fclose;
use function fopen;
use function fwrite;
use function in_array;
use function rtrim;
use function unlink;

class Dumper
{
    public function dump(DumpConfiguration $dumpConfiguration): void
    {
        $connection = DriverManager::getConnection($dumpConfiguration->toParams());
        $tables = $connection->getSchemaManager()->listTables();

        $folder = rtrim($dumpConfiguration->folder, '/') . '/';
        mkdir_deep($folder);

        $files = [];

        $preamble = $folder . '_preamble.sql';
        $files[] = $preamble;
        $handle = fopen($preamble, 'wb');

        $platform = new MysqlOrderedCreateTableSql();

        $emptyTables = [];
        foreach ($tables as $table) {
            $query = $connection->createQueryBuilder();
            $query->select('count(*) AS CNT')->from($table->getName());
            $count = $query->executeQuery()->fetchOne();
            if ($count === 0) {
                $emptyTables[] = $table->getName();
            }
        }

        if ($dumpConfiguration->recreateTables) {
            foreach ($tables as $table) {
                if (!in_array($table->getName(), $emptyTables)) {
                    $drop = $connection->getSchemaManager()->getDatabasePlatform()->getDropTableSQL($table);
                    fwrite($handle, $drop . ";\n");
                }
            }
            foreach ($tables as $table) {
                if (!in_array($table->getName(), $emptyTables)) {
                    $createStatements = $platform->getCreateTableSQL($table);
                    foreach ($createStatements as $statement) {
                        fwrite($handle, $statement . ";\n");
                    }
                }
            }
        }

        fclose($handle);

        foreach ($tables as $table) {
            $tableName = $table->getName();
            if (!in_array($table->getName(), $emptyTables) && !$dumpConfiguration->isExcluded($tableName)) {
                $connection->executeStatement(
                    sprintf(
                        'SELECT * INTO OUTFILE \'%s\' FROM %s',
                        $folder . $tableName . '.csv',
                        $tableName,
                    ),
                );

                $files[] = $folder . $tableName . '.csv';
            }
        }

        if ($dumpConfiguration->zip) {
            $zip = new ZipArchive();
            $zip->open($folder . '/mysql-loader.zip', ZipArchive::CREATE);
            foreach ($files as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }
}
