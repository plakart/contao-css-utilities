<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\EventListener\DataContainer;

use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\ClassSelection\ClassGroupRepository;
use Plakart\CssUtilitiesBundle\EventListener\DataContainer\ClassSelectionPaletteListener;
use Plakart\CssUtilitiesBundle\EventListener\DataContainer\UtilityPaletteListener;
use Plakart\CssUtilitiesBundle\Target\TargetRegistry;
use Plakart\CssUtilitiesBundle\Tests\DatabaseTestTrait;

final class ClassSelectionPaletteListenerTest extends TestCase
{
    use DatabaseTestTrait;

    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = self::createConnection();

        self::insertGroup($this->connection, ['alias' => 'buttons', 'targets' => ['content', 'page'], 'contentTypes' => ['hyperlink']], ['btn' => 'Button']);

        $GLOBALS['TL_DCA']['tl_content']['palettes'] = [
            '__selector__' => ['addImage'],
            'hyperlink' => '{type_legend},type;{expert_legend:hide},cssID',
            'text' => '{type_legend},type;{expert_legend:hide},cssID',
        ];

        $GLOBALS['TL_DCA']['tl_page']['palettes'] = [
            'regular' => '{title_legend},title;{expert_legend:hide},cssClass',
        ];

        $GLOBALS['TL_DCA']['tl_module']['palettes'] = [
            'navigation' => '{title_legend},name;{expert_legend:hide},cssID',
        ];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testAddsFieldAfterSpacingFieldsInTheSameLegend(): void
    {
        $this->connection->insert('tl_content', ['type' => 'hyperlink']);

        $this->invoke('tl_content', (int) $this->connection->lastInsertId());

        self::assertSame(
            '{type_legend},type;{pcu_classes_legend:hide},utilityMt,utilityMb,utilityPt,utilityPb,pcuClasses;{expert_legend:hide},cssID',
            $GLOBALS['TL_DCA']['tl_content']['palettes']['hyperlink'],
        );
        self::assertSame(['addImage'], $GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__']);
    }

    public function testOnlyAddsSpacingFieldsForOtherTypes(): void
    {
        $this->connection->insert('tl_content', ['type' => 'text']);

        $this->invoke('tl_content', (int) $this->connection->lastInsertId());

        self::assertSame(
            '{type_legend},type;{pcu_classes_legend:hide},utilityMt,utilityMb,utilityPt,utilityPb;{expert_legend:hide},cssID',
            $GLOBALS['TL_DCA']['tl_content']['palettes']['text'],
        );
    }

    public function testAddsFieldWithoutRecordIdIfAnyGroupTargetsTheTable(): void
    {
        $this->invoke('tl_content', null);

        self::assertStringContainsString('pcuClasses', $GLOBALS['TL_DCA']['tl_content']['palettes']['text']);
    }

    public function testAddsFieldBeforeExpertLegendOnTablesWithoutSpacing(): void
    {
        $this->connection->insert('tl_page', ['type' => 'regular']);

        $this->invoke('tl_page', (int) $this->connection->lastInsertId());

        self::assertSame(
            '{title_legend},title;{pcu_classes_legend:hide},pcuClasses;{expert_legend:hide},cssClass',
            $GLOBALS['TL_DCA']['tl_page']['palettes']['regular'],
        );
    }

    public function testLeavesTablesWithoutGroupsUntouched(): void
    {
        $this->connection->insert('tl_module', ['type' => 'navigation']);

        $this->invoke('tl_module', (int) $this->connection->lastInsertId());

        self::assertSame('{title_legend},name;{expert_legend:hide},cssID', $GLOBALS['TL_DCA']['tl_module']['palettes']['navigation']);
    }

    private function invoke(string $table, ?int $id): void
    {
        $dc = $this->createStub(DataContainer::class);
        $dc->method('__get')->willReturnMap([['table', $table], ['id', $id]]);

        $listener = new ClassSelectionPaletteListener(
            new TargetRegistry([]),
            new ClassGroupRepository($this->connection),
            $this->connection,
            new UtilityPaletteListener(),
        );

        $listener($dc);
    }
}
