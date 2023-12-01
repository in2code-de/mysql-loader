<?php

declare(strict_types=1);

namespace CoStack\MysqlLoader;

class ImportConfiguration
{
    public function __construct(
        #[SensitiveParameter] public readonly string $host,
        #[SensitiveParameter] public readonly int $port,
        #[SensitiveParameter] public readonly string $user,
        #[SensitiveParameter] public readonly string $password,
        #[SensitiveParameter] public readonly string $dbname,
        public readonly string $file,
    ) {
    }

    public static function fromParams(
        #[SensitiveParameter] array $params,
        string $file,
    ): DumpConfiguration {
        return new DumpConfiguration(
            $params['host'] ?? 'localhost',
            $params['port'] ?? 3306,
            $params['user'],
            $params['password'],
            $params['dbname'],
            $file,
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
}
