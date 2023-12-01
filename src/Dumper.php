<?php

declare(strict_types=1);

namespace CoStack\MysqlLoader;

use ArrayObject;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use ZipArchive;

use function basename;
use function fclose;
use function file_exists;
use function fopen;
use function fwrite;
use function hash_file;
use function implode;
use function rename;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;
use function unlink;

class Dumper
{
    public function dump(DumpConfiguration $dumpConfiguration): void
    {
        $connection = DriverManager::getConnection($dumpConfiguration->toParams());
        $dbInfo = (new DatabaseManager())->getDatabaseInformation($connection, $dumpConfiguration);

        $files = new ArrayObject();

        $this->createPreamble($dumpConfiguration, $dbInfo, $connection, $files);
        $this->dumpTables($dumpConfiguration, $dbInfo, $connection, $files);

        if ($dumpConfiguration->zip) {
            $zip = new ZipArchive();
            $zip->open($dumpConfiguration->folder . 'mysql-loader.zip', ZipArchive::CREATE);
            foreach ($files as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    protected function createPreamble(
        DumpConfiguration $dumpConfiguration,
        DatabaseInfo $dbInfo,
        Connection $connection,
        ArrayObject $files,
    ): void {
        $preamble = $dumpConfiguration->folder . '_preamble.sql';
        if (file_exists($preamble)) {
            rename($preamble, $preamble . '.bak');
        }
        $handle = fopen($preamble, 'wb');

        $drops = $creates = $truncates = [];

        if ($dumpConfiguration->recreateTables) {
            foreach ($dbInfo->tables as $tableName => $table) {
                if ($dbInfo->isEmptyTable($table)) {
                    continue;
                }
                if ($dbInfo->isExcludedTable($table)) {
                    if ($dumpConfiguration->truncateIgnoredTables) {
                        $truncates[] = $connection->getDatabasePlatform()->getTruncateTableSQL($tableName);
                    }
                    continue;
                }
                if ($dumpConfiguration->truncateInsteadOfRecreate) {
                    $truncates[] = $connection->getDatabasePlatform()->getTruncateTableSQL($tableName);
                } else {
                    $drops[] = $connection->getSchemaManager()->getDatabasePlatform()->getDropTableSQL($table);
                    $statements = $connection->getSchemaManager()->getDatabasePlatform()->getCreateTableSQL($table);
                    foreach ($statements as $statement) {
                        $creates[] = $statement;
                    }
                }
            }
        } elseif ($dumpConfiguration->truncateIgnoredTables) {
            foreach ($dbInfo->excludedTables as $tableName) {
                $truncates[] = $connection->getDatabasePlatform()->getTruncateTableSQL($tableName);
            }
        }
        foreach ($drops as $drop) {
            fwrite($handle, $drop . ";\n");
        }
        foreach ($creates as $create) {
            fwrite($handle, $create . ";\n");
        }
        foreach ($truncates as $truncate) {
            fwrite($handle, $truncate . ";\n");
        }

        fclose($handle);
        if (
            file_exists($preamble . '.bak')
            && hash_file('sha1', $preamble) === hash_file('sha1', $preamble . '.bak')
        ) {
            unlink($preamble);
            rename($preamble . '.bak', $preamble);
        }
        $files[] = $preamble;
    }

    protected function dumpTables(
        DumpConfiguration $dumpConfiguration,
        DatabaseInfo $dbInfo,
        Connection $connection,
        ArrayObject $files,
    ): void {
        foreach ($dbInfo->nonEmptyNonExcludedTableNames as $tableName) {
            $fileName = $dumpConfiguration->folder . $tableName . '.csv';
            if (file_exists($fileName)) {
                rename($fileName, $fileName . '.bak');
            }
            $queries = [];
            foreach ($dumpConfiguration->filterQuery as $query) {
                if (str_starts_with($query, $tableName . ':')) {
                    $queries[] = substr($query, strlen($tableName . ':'));
                }
            }
            $where = implode(' AND ', $queries);
            if (!empty($where)) {
                $connection->executeStatement(
                    sprintf('SELECT * INTO OUTFILE \'%s\' FROM %s WHERE %s', $fileName, $tableName, $where),
                );
            } else {
                $connection->executeStatement(sprintf('SELECT * INTO OUTFILE \'%s\' FROM %s', $fileName, $tableName));
            }
            $files[] = $fileName;
            if (
                file_exists($fileName . '.bak')
                && hash_file('sha1', $fileName) === hash_file('sha1', $fileName . '.bak')
            ) {
                unlink($fileName);
                rename($fileName . '.bak', $fileName);
            }
        }
    }
}
