<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\EventListener\DataContainer;

use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\ClassSelection\ClassGroupRepository;
use Plakart\CssUtilitiesBundle\EventListener\DataContainer\ClassAttributeListener;
use Plakart\CssUtilitiesBundle\Target\TargetRegistry;
use Plakart\CssUtilitiesBundle\Tests\DatabaseTestTrait;

final class ClassAttributeListenerTest extends TestCase
{
    use DatabaseTestTrait;

    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = self::createConnection();

        self::insertGroup($this->connection, ['title' => 'Buttons', 'alias' => 'buttons', 'targets' => ['content', 'page'], 'contentTypes' => ['hyperlink']], [
            'btn-primary' => 'Primary',
            'btn-secondary' => 'Secondary',
        ]);
        self::insertGroup($this->connection, ['title' => 'Width', 'alias' => 'width', 'multiple' => 1, 'targets' => ['content', 'article', 'form_field']], [
            'full--width' => 'Full',
            'boxed narrow' => 'Boxed',
            'retired' => ['label' => 'Retired', 'published' => 0],
        ]);
    }

    public function testMergesSpacingAndSelectionIntoCssId(): void
    {
        $id = $this->insert('tl_content', [
            'type' => 'hyperlink',
            'cssID' => serialize(['hero', 'block mt-2 btn-secondary retired']),
            'utilityMt' => '4',
            'utilityPt' => '0',
            'pcuClasses' => serialize(['buttons' => ['btn-primary'], 'width' => ['full--width', 'boxed narrow']]),
        ]);

        $this->invoke('tl_content', $id);

        self::assertSame(
            serialize(['hero', 'block mt-4 pt-0 btn-primary full--width boxed narrow']),
            $this->fetch('tl_content', $id, 'cssID'),
        );
    }

    public function testDropsGroupsThatNoLongerApply(): void
    {
        $id = $this->insert('tl_content', [
            'type' => 'text',
            'cssID' => serialize(['', 'btn-primary full--width']),
            'pcuClasses' => serialize(['buttons' => ['btn-primary'], 'width' => ['full--width']]),
        ]);

        $this->invoke('tl_content', $id);

        self::assertSame(serialize(['', 'full--width']), $this->fetch('tl_content', $id, 'cssID'));
        self::assertSame(serialize(['width' => ['full--width']]), $this->fetch('tl_content', $id, 'pcuClasses'));
    }

    public function testClearsStoredSelectionWhenNothingApplies(): void
    {
        $id = $this->insert('tl_content', [
            'type' => 'text',
            'cssID' => serialize(['', 'foo btn-primary']),
            'pcuClasses' => serialize(['buttons' => ['btn-primary']]),
        ]);

        $this->invoke('tl_content', $id);

        self::assertSame(serialize(['', 'foo']), $this->fetch('tl_content', $id, 'cssID'));
        self::assertNull($this->fetch('tl_content', $id, 'pcuClasses'));
    }

    public function testHandlesEmptyCssIdColumn(): void
    {
        $id = $this->insert('tl_article', ['cssID' => null, 'utilityMb' => '3', 'pcuClasses' => serialize(['width' => ['full--width']])]);

        $this->invoke('tl_article', $id);

        self::assertSame(serialize(['', 'mb-3 full--width']), $this->fetch('tl_article', $id, 'cssID'));
    }

    public function testWritesFormFieldClass(): void
    {
        $id = $this->insert('tl_form_field', ['type' => 'text', 'class' => 'custom pb-1', 'utilityPb' => '8', 'pcuClasses' => serialize(['width' => ['boxed narrow']])]);

        $this->invoke('tl_form_field', $id);

        self::assertSame('custom pb-8 boxed narrow', $this->fetch('tl_form_field', $id, 'class'));
    }

    public function testWritesPageCssClassWithoutTouchingSpacingClasses(): void
    {
        $id = $this->insert('tl_page', ['type' => 'regular', 'cssClass' => 'home mt-4 btn-secondary', 'pcuClasses' => serialize(['buttons' => ['btn-primary']])]);

        $this->invoke('tl_page', $id);

        self::assertSame('home mt-4 btn-primary', $this->fetch('tl_page', $id, 'cssClass'));
    }

    public function testSkipsUnchangedRecords(): void
    {
        $id = $this->insert('tl_module', ['type' => 'navigation', 'cssID' => null]);

        $listener = new ClassAttributeListener($this->connection, new TargetRegistry([]), new ClassGroupRepository($this->connection));
        $row = $this->connection->fetchAssociative('SELECT * FROM tl_module WHERE id = ?', [$id]);

        self::assertIsArray($row);
        self::assertSame([], $listener->computeUpdates(new TargetRegistry([])->get('module'), $row));
    }

    public function testIgnoresTablesOfMissingBundles(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::never())->method('fetchAssociative');

        $dc = $this->createStub(DataContainer::class);
        $dc->method('__get')->willReturnMap([['table', 'tl_news'], ['id', 1]]);

        (new ClassAttributeListener($connection, new TargetRegistry([]), new ClassGroupRepository($connection)))($dc);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function insert(string $table, array $row): int
    {
        $this->connection->insert($table, $row);

        return (int) $this->connection->lastInsertId();
    }

    private function fetch(string $table, int $id, string $column): mixed
    {
        return $this->connection->fetchOne(\sprintf('SELECT %s FROM %s WHERE id = ?', $column, $table), [$id]);
    }

    private function invoke(string $table, int $id): void
    {
        $dc = $this->createStub(DataContainer::class);
        $dc->method('__get')->willReturnMap([['table', $table], ['id', $id]]);

        (new ClassAttributeListener($this->connection, new TargetRegistry([]), new ClassGroupRepository($this->connection)))($dc);
    }
}
