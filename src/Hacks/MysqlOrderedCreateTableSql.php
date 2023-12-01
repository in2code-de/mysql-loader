<?php

declare(strict_types=1);

namespace CoStack\MysqlLoader\Hacks;

use Doctrine\DBAL\Event\SchemaCreateTableColumnEventArgs;
use Doctrine\DBAL\Event\SchemaCreateTableEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Deprecations\Deprecation;
use InvalidArgumentException;
use ReflectionProperty;

use function array_merge;
use function count;
use function in_array;
use function is_int;

class MysqlOrderedCreateTableSql extends MySQL80Platform
{
    /**
     * Returns the SQL statement(s) to create a table with the specified name, columns and constraints
     * on this platform.
     *
     * @param int $createFlags
     * @psalm-param int-mask-of<self::CREATE_*> $createFlags
     *
     * @return list<string> The list of SQL statements.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getCreateTableSQL(Table $table, $createFlags = self::CREATE_INDEXES)
    {
        if (!is_int($createFlags)) {
            throw new InvalidArgumentException(
                'Second argument of AbstractPlatform::getCreateTableSQL() has to be integer.',
            );
        }

        if (($createFlags & self::CREATE_INDEXES) === 0) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/5416',
                'Unsetting the CREATE_INDEXES flag in AbstractPlatform::getCreateTableSQL() is deprecated.',
            );
        }

        if (($createFlags & self::CREATE_FOREIGNKEYS) === 0) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/5416',
                'Not setting the CREATE_FOREIGNKEYS flag in AbstractPlatform::getCreateTableSQL()'
                . ' is deprecated. In order to build the statements that create multiple tables'
                . ' referencing each other via foreign keys, use AbstractPlatform::getCreateTablesSQL().',
            );
        }

        return $this->buildCreateTableSQL(
            $table,
            ($createFlags & self::CREATE_INDEXES) > 0,
            ($createFlags & self::CREATE_FOREIGNKEYS) > 0,
        );
    }

    /**
     * @return list<string>
     *
     * @throws Exception
     */
    private function buildCreateTableSQL(Table $table, bool $createIndexes, bool $createForeignKeys): array
    {
        if (count($table->getColumns()) === 0) {
            throw Exception::noColumnsSpecifiedForTable($table->getName());
        }

        $tableName = $table->getQuotedName($this);
        $options = $table->getOptions();
        $options['uniqueConstraints'] = [];
        $options['indexes'] = [];
        $options['primary'] = [];

        if ($createIndexes) {
            foreach ($table->getIndexes() as $index) {
                if (!$index->isPrimary()) {
                    $options['indexes'][$index->getQuotedName($this)] = $index;

                    continue;
                }

                $options['primary'] = $index->getQuotedColumns($this);
                $options['primary_index'] = $index;
            }

            foreach ($table->getUniqueConstraints() as $uniqueConstraint) {
                $options['uniqueConstraints'][$uniqueConstraint->getQuotedName($this)] = $uniqueConstraint;
            }
        }

        if ($createForeignKeys) {
            $options['foreignKeys'] = [];

            foreach ($table->getForeignKeys() as $fkConstraint) {
                $options['foreignKeys'][] = $fkConstraint;
            }
        }

        $columnSql = [];
        $columns = [];

        $refl = new ReflectionProperty($table, '_columns');
        $refl->setAccessible(true);
        $tableColumns = $refl->getValue($table);
        foreach ($tableColumns as $column) {
            if (
                $this->_eventManager !== null
                && $this->_eventManager->hasListeners(Events::onSchemaCreateTableColumn)
            ) {
                Deprecation::trigger(
                    'doctrine/dbal',
                    'https://github.com/doctrine/dbal/issues/5784',
                    'Subscribing to %s events is deprecated.',
                    Events::onSchemaCreateTableColumn,
                );

                $eventArgs = new SchemaCreateTableColumnEventArgs($column, $table, $this);

                $this->_eventManager->dispatchEvent(Events::onSchemaCreateTableColumn, $eventArgs);

                $columnSql = array_merge($columnSql, $eventArgs->getSql());

                if ($eventArgs->isDefaultPrevented()) {
                    continue;
                }
            }

            $columnData = $this->columnToArray($column);

            if (in_array($column->getName(), $options['primary'], true)) {
                $columnData['primary'] = true;
            }

            $columns[$columnData['name']] = $columnData;
        }

        if ($this->_eventManager !== null && $this->_eventManager->hasListeners(Events::onSchemaCreateTable)) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/issues/5784',
                'Subscribing to %s events is deprecated.',
                Events::onSchemaCreateTable,
            );

            $eventArgs = new SchemaCreateTableEventArgs($table, $columns, $options, $this);

            $this->_eventManager->dispatchEvent(Events::onSchemaCreateTable, $eventArgs);

            if ($eventArgs->isDefaultPrevented()) {
                return array_merge($eventArgs->getSql(), $columnSql);
            }
        }

        $sql = $this->_getCreateTableSQL($tableName, $columns, $options);

        if ($this->supportsCommentOnStatement()) {
            if ($table->hasOption('comment')) {
                $sql[] = $this->getCommentOnTableSQL($tableName, $table->getOption('comment'));
            }

            foreach ($table->getColumns() as $column) {
                $comment = $this->getColumnComment($column);

                if ($comment === null || $comment === '') {
                    continue;
                }

                $sql[] = $this->getCommentOnColumnSQL($tableName, $column->getQuotedName($this), $comment);
            }
        }

        return array_merge($sql, $columnSql);
    }

    private function columnToArray(Column $column): array
    {
        $name = $column->getQuotedName($this);

        return array_merge($column->toArray(), [
            'name' => $name,
            'version' => $column->hasPlatformOption('version') ? $column->getPlatformOption('version') : false,
            'comment' => $this->getColumnComment($column),
        ]);
    }
}
