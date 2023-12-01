<?php

declare(strict_types=1);

namespace CoStack\MysqlLoader;

use Doctrine\DBAL\Schema\Column;

class Table extends \Doctrine\DBAL\Schema\Table
{
    public static function fromTable(\Doctrine\DBAL\Schema\Table $table): static
    {
        $object = new Table(
            $table->_name,
            $table->_columns,
            $table->_indexes,
            $table->uniqueConstraints,
            $table->_fkConstraints,
            $table->_options,
        );
        return $object;
    }

    /** @return array<Column> */
    public function getColumns(): array
    {
        return $this->_columns;
    }
}
