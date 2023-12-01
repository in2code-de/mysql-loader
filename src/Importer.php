<?php

declare(strict_types=1);

namespace CoStack\MysqlLoader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

use ZipArchive;

use function dirname;
use function exec;
use function file_exists;
use function file_get_contents;
use function is_dir;
use function register_shutdown_function;
use function rmdir;
use function rtrim;
use function scandir;
use function sprintf;
use function str_ends_with;
use function uniqid;
use function unlink;

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
        $connection->executeStatement(file_get_contents($folder . '_preamble.sql'));

        $tables = $connection->getSchemaManager()->listTableNames();

        foreach ($tables as $table) {
            $inFile = $folder . $table . '.csv';
            if (file_exists($inFile)) {
                $connection->executeStatement(
                    sprintf(
                        'LOAD DATA INFILE \'%s\' INTO TABLE %s',
                        $inFile,
                        $table,
                    ),
                );
            }
        }
    }
}
