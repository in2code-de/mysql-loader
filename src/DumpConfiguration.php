<?php

declare(strict_types=1);

namespace CoStack\MysqlLoader;

use SensitiveParameter;

use function preg_match;
use function preg_quote;

class DumpConfiguration
{
    public function __construct(
        #[SensitiveParameter] public readonly string $host,
        #[SensitiveParameter] public readonly int $port,
        #[SensitiveParameter] public readonly string $user,
        #[SensitiveParameter] public readonly string $password,
        #[SensitiveParameter] public readonly string $dbname,
        public readonly string $folder,
        public array $excludedTablesPatterns = [],
        public bool $recreateTables = true,
        public bool $zip = true,
    ) {
    }

    public static function fromParams(
        #[SensitiveParameter] array $params,
        string $folder,
        array $excludedTablesPatterns = [],
        bool $recreateTables = true,
        bool $zip = true,
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
            $zip,
        );
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
            if (1 === preg_match('/' . preg_quote($pattern, '/') . '/', $table)) {
                return true;
            }
        }
        return false;
    }
}
