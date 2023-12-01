<?php

declare(strict_types=1);

namespace CoStack\MysqlLoader;

use Doctrine\DBAL\DriverManager;

use ZipArchive;

use function dirname;
use function file_exists;
use function file_get_contents;
use function rmdir;
use function sprintf;
use function unlink;

class Importer
{
    public function import(ImportConfiguration $importConfiguration): void
    {
        $zip = new ZipArchive();
        $zip->open($importConfiguration->file);
        $tempPath = dirname($importConfiguration->file) . '/tmp/';
        $zip->extractTo($tempPath);

        $connection = DriverManager::getConnection($importConfiguration->toParams());
        $connection->executeStatement(file_get_contents($tempPath . '_preamble.sql'));
        unlink($tempPath . '_preamble.sql');

        $tables = $connection->getSchemaManager()->listTableNames();

        foreach ($tables as $table) {
            $inFile = $tempPath . $table . '.csv';
            if (file_exists($inFile)) {
                $connection->executeStatement(
                    sprintf(
                        'LOAD DATA INFILE \'%s\' INTO TABLE %s',
                        $inFile,
                        $table,
                    ),
                );
                unlink($inFile);
            }
        }
        rmdir($tempPath);
    }
}
