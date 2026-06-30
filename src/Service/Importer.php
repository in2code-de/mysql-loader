<?php

declare(strict_types=1);

namespace CoStack\MysqlLoader\Service;

use CoStack\MysqlLoader\ImportConfiguration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use ZipArchive;

use function dirname;
use function exec;
use function file_exists;
use function file_get_contents;
use function is_callable;
use function is_dir;
use function register_shutdown_function;
use function sprintf;
use function str_ends_with;
use function trim;
use function uniqid;

class Importer
{
    public function import(ImportConfiguration $importConfiguration): void
    {
        $connection = DriverManager::getConnection($importConfiguration->toParams());

        if (str_ends_with($importConfiguration->fileOrFolder, '.zip')) {
            $this->importFromZip($importConfiguration, $connection);
            return;
        }

        $this->importFromFolder($importConfiguration->fileOrFolder, $connection);
    }

    protected function importFromZip(ImportConfiguration $importConfiguration, Connection $connection): void
    {
        $zip = new ZipArchive();
        $zip->open($importConfiguration->fileOrFolder);
        do {
            /** @noinspection PhpRedundantOptionalArgumentInspection */
            $folder = dirname($importConfiguration->fileOrFolder) . '/' . uniqid('tmp_', false);
        } while (is_dir($folder));
        register_shutdown_function(static function () use ($folder): void {
            if (is_dir($folder)) {
                exec('rm -rf ' . $folder);
            }
        });

        $zip->extractTo($folder);

        $this->importFromFolder($folder, $connection);

        exec('rm -rf ' . $folder);
    }

    protected function importFromFolder(string $folder, Connection $connection): void
    {
        if (file_exists($folder . '_preamble.sql')) {
            $preamble = trim((string)file_get_contents($folder . '_preamble.sql'));
            $statements = array_filter(
                array_map('trim', explode(';', $preamble)),
                static fn(string $s): bool => $s !== '',
            );
            foreach ($statements as $statement) {
                $connection->executeStatement($statement);
            }
        }

        if (is_callable([$connection, 'createSchemaManager'])) {
            // Doctrine 4.x
            $tables = $connection->createSchemaManager()->listTableNames();
        } else {
            // Doctrine 3.x
            $tables = $connection->getSchemaManager()->listTableNames();
        }

        foreach ($tables as $table) {
            $inFile = $folder . $table . '.csv';
            if (file_exists($inFile)) {
                try {
                    $connection->executeStatement(
                        $connection->getDatabasePlatform()->getTruncateTableSQL($table),
                    );
                    $connection->executeStatement(sprintf('LOAD DATA INFILE \'%s\' INTO TABLE %s', $inFile, $table));
                } catch (Exception $e) {
                    throw new \Exception(
                        'Error while importing data from ' . $table . ': ' . $e->getMessage(),
                        1701699938,
                        $e,
                    );
                }
            }
        }
    }
}
