<?php

declare(strict_types=1);

namespace CoStack\MysqlLoader;

use SensitiveParameter;

use function CoStack\Lib\concat_paths;
use function CoStack\Lib\mkdir_deep;
use function getcwd;
use function preg_match;
use function preg_quote;
use function rtrim;
use function str_starts_with;

class DumpConfiguration
{
    public readonly string $folder;

    public function __construct(
        #[SensitiveParameter] public readonly string $host,
        #[SensitiveParameter] public readonly int $port,
        #[SensitiveParameter] public readonly string $user,
        #[SensitiveParameter] public readonly string $password,
        #[SensitiveParameter] public readonly string $dbname,
        string $folder,
        public array $excludedTablesPatterns = [],
        public bool $recreateTables = true,
        public bool $truncateInsteadOfRecreate = false,
        public bool $truncateIgnoredTables = true,
        public bool $zip = false,
        public array $filterQuery = [],
    ) {
        $this->folder = $this->normalizeAndCreateFolder($folder);
    }

    public static function fromParams(
        #[SensitiveParameter] array $params,
        string $folder,
        array $excludedTablesPatterns = [],
        bool $recreateTables = true,
        bool $truncateInsteadOfRecreate = false,
        bool $truncateIgnoredTables = true,
        bool $zip = false,
        array $filterQuery = [],
    ): DumpConfiguration {
        return new DumpConfiguration(
            $params['host'] ?? 'localhost',
            $params['port'] ?? 3306,
            $params['user'],
            $params['password'],
            $params['dbname'],
            $folder,
            $excludedTablesPatterns,
            $recreateTables,
            $truncateInsteadOfRecreate,
            $truncateIgnoredTables,
            $zip,
            $filterQuery,
        );
    }

    protected function normalizeAndCreateFolder(string $folder): string
    {
        if (!str_starts_with($folder, '/')) {
            $folder = concat_paths(getcwd(), $folder);
        }
        $folder = rtrim($folder, '/') . '/';
        mkdir_deep($folder);
        return $folder;
    }

    public function toParams(): array
    {
        return [
            'dbname' => $this->dbname,
            'user' => $this->user,
            'password' => $this->password,
            'host' => $this->host,
            'driver' => 'pdo_mysql',
        ];
    }

    public function isExcluded(string $table): bool
    {
        foreach ($this->excludedTablesPatterns as $pattern) {
            $quotedPattern = preg_quote($pattern, '/');
            $finalPatter = '/' . $quotedPattern . '/';
            if (1 === preg_match($finalPatter, $table)) {
                return true;
            }
        }
        return false;
    }
}
