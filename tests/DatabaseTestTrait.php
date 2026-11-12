<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;

/**
 * In-memory SQLite database with the bundle tables.
 */
trait DatabaseTestTrait
{
    private static function createConnection(): Connection
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);

        $schema = new Schema();

        $group = $schema->createTable('tl_pcu_class_group');
        $group->addColumn('id', 'integer', ['autoincrement' => true]);
        $group->addColumn('tstamp', 'integer', ['default' => 0]);
        $group->addColumn('title', 'string', ['length' => 255, 'default' => '']);
        $group->addColumn('alias', 'string', ['length' => 64, 'default' => '']);
        $group->addColumn('multiple', 'boolean', ['default' => false]);
        $group->addColumn('targets', 'blob', ['notnull' => false]);
        $group->addColumn('contentTypes', 'blob', ['notnull' => false]);
        $group->addColumn('moduleTypes', 'blob', ['notnull' => false]);
        $group->addColumn('formFieldTypes', 'blob', ['notnull' => false]);
        $group->addColumn('published', 'boolean', ['default' => true]);
        $group->setPrimaryKey(['id']);

        $class = $schema->createTable('tl_pcu_class');
        $class->addColumn('id', 'integer', ['autoincrement' => true]);
        $class->addColumn('pid', 'integer', ['default' => 0]);
        $class->addColumn('tstamp', 'integer', ['default' => 0]);
        $class->addColumn('sorting', 'integer', ['default' => 0]);
        $class->addColumn('label', 'string', ['length' => 255, 'default' => '']);
        $class->addColumn('cssClass', 'string', ['length' => 255, 'default' => '']);
        $class->addColumn('published', 'boolean', ['default' => true]);
        $class->setPrimaryKey(['id']);

        foreach (['tl_content' => 'cssID', 'tl_article' => 'cssID', 'tl_form_field' => 'class', 'tl_module' => 'cssID', 'tl_page' => 'cssClass'] as $name => $column) {
            $table = $schema->createTable($name);
            $table->addColumn('id', 'integer', ['autoincrement' => true]);
            $table->addColumn('type', 'string', ['length' => 64, 'default' => '']);
            $table->addColumn($column, 'text', ['notnull' => false]);
            $table->addColumn('pcuClasses', 'blob', ['notnull' => false]);

            foreach (['utilityMt', 'utilityMb', 'utilityPt', 'utilityPb'] as $field) {
                $table->addColumn($field, 'string', ['length' => 2, 'default' => '']);
            }

            $table->setPrimaryKey(['id']);
        }

        foreach ($schema->toSql($connection->getDatabasePlatform()) as $sql) {
            $connection->executeStatement($sql);
        }

        return $connection;
    }

    /**
     * @param array<string, mixed>                       $group
     * @param array<string, string|array<string, mixed>> $classes cssClass => label, or a full row
     */
    private static function insertGroup(Connection $connection, array $group, array $classes = []): int
    {
        foreach (['targets', 'contentTypes', 'moduleTypes', 'formFieldTypes'] as $field) {
            if (isset($group[$field]) && \is_array($group[$field])) {
                $group[$field] = serialize($group[$field]);
            }
        }

        $connection->insert('tl_pcu_class_group', $group + ['title' => 'Group', 'alias' => 'group', 'published' => 1]);
        $id = (int) $connection->lastInsertId();
        $sorting = 0;

        foreach ($classes as $cssClass => $class) {
            $row = \is_array($class) ? $class : ['label' => $class];
            $sorting += 128;

            $connection->insert('tl_pcu_class', $row + ['pid' => $id, 'cssClass' => $cssClass, 'sorting' => $sorting, 'published' => 1]);
        }

        return $id;
    }
}
