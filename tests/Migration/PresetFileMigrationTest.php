<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\Migration;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\Migration\PresetFileMigration;
use Plakart\CssUtilitiesBundle\Preset\PresetImporter;
use Plakart\CssUtilitiesBundle\Tests\DatabaseTestTrait;

final class PresetFileMigrationTest extends TestCase
{
    use DatabaseTestTrait;

    private Connection $connection;

    private string $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = self::createConnection();
        $this->file = sys_get_temp_dir().'/pcu-preset-'.bin2hex(random_bytes(4)).'.json';
    }

    protected function tearDown(): void
    {
        if (is_file($this->file)) {
            unlink($this->file);
        }

        parent::tearDown();
    }

    public function testImportsPresetOnce(): void
    {
        copy(__DIR__.'/../Fixtures/preset.json', $this->file);

        $migration = $this->createMigration();

        self::assertTrue($migration->shouldRun());

        $result = $migration->run();

        self::assertTrue($result->isSuccessful());
        self::assertStringContainsString('Imported 2 CSS class groups, 3 classes and 0 additional spacing steps', $result->getMessage());
        self::assertFalse($migration->shouldRun());
    }

    public function testDoesNotRunWithoutFile(): void
    {
        self::assertFalse($this->createMigration()->shouldRun());
    }

    public function testDoesNotRunWithoutTables(): void
    {
        copy(__DIR__.'/../Fixtures/preset.json', $this->file);

        $this->connection->executeStatement('DROP TABLE tl_pcu_class_group');

        self::assertFalse($this->createMigration()->shouldRun());
    }

    public function testDoesNotRunIfGroupsExist(): void
    {
        copy(__DIR__.'/../Fixtures/preset.json', $this->file);

        self::insertGroup($this->connection, ['alias' => 'mine']);

        self::assertFalse($this->createMigration()->shouldRun());
    }

    public function testDoesNotRunWithInvalidFile(): void
    {
        file_put_contents($this->file, '{"format":"other"}');

        self::assertFalse($this->createMigration()->shouldRun());
    }

    public function testDoesNotRunWithEmptyPreset(): void
    {
        file_put_contents($this->file, '{"format":"plakart-css-classes","version":1,"groups":[]}');

        self::assertFalse($this->createMigration()->shouldRun());
    }

    private function createMigration(): PresetFileMigration
    {
        return new PresetFileMigration($this->connection, new PresetImporter($this->connection), $this->file);
    }
}
