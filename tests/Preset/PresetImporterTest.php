<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\Preset;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\Preset\Preset;
use Plakart\CssUtilitiesBundle\Preset\PresetExporter;
use Plakart\CssUtilitiesBundle\Preset\PresetImporter;
use Plakart\CssUtilitiesBundle\Preset\PresetSerializer;
use Plakart\CssUtilitiesBundle\Tests\DatabaseTestTrait;

final class PresetImporterTest extends TestCase
{
    use DatabaseTestTrait;

    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = self::createConnection();
    }

    public function testImportsEverythingIntoEmptyDatabase(): void
    {
        $preset = $this->fixture();

        self::assertSame(['groups' => 2, 'classes' => 3, 'updatedGroups' => 0, 'updatedClasses' => 0], new PresetImporter($this->connection)->apply($preset));

        $group = $this->connection->fetchAssociative("SELECT * FROM tl_pcu_class_group WHERE alias = 'buttons'");
        self::assertIsArray($group);
        self::assertSame('Button-Stil', $group['title']);
        self::assertSame(serialize(['content']), $group['targets']);
        self::assertSame(serialize(['hyperlink']), $group['contentTypes']);
        self::assertNull($group['moduleTypes']);
        self::assertGreaterThan(0, (int) $group['tstamp']);

        $classes = $this->connection->fetchAllAssociative('SELECT cssClass, label, sorting, published FROM tl_pcu_class WHERE pid = ? ORDER BY sorting', [$group['id']]);
        self::assertSame('btn_hyperlink_primary', $classes[0]['cssClass']);
        self::assertSame(128, (int) $classes[0]['sorting']);
        self::assertSame(1, (int) $classes[0]['published']);
        self::assertSame(256, (int) $classes[1]['sorting']);
        self::assertSame(0, (int) $classes[1]['published']);

        // Round trip through the exporter (exports are ordered by title)
        $groups = $preset->groups;
        usort($groups, static fn ($a, $b): int => strcmp($a->title, $b->title));

        self::assertSame(PresetSerializer::toJson(new Preset($groups)), PresetSerializer::toJson(new PresetExporter($this->connection)->export()));
    }

    public function testSecondImportIsANoOp(): void
    {
        $importer = new PresetImporter($this->connection);
        $importer->apply($this->fixture());

        self::assertFalse($importer->plan($this->fixture())->hasChanges());
        self::assertSame(['groups' => 0, 'classes' => 0, 'updatedGroups' => 0, 'updatedClasses' => 0], $importer->apply($this->fixture()));
        self::assertSame(3, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tl_pcu_class'));
    }

    public function testPlanAndAppendToExistingGroup(): void
    {
        $groupId = self::insertGroup($this->connection, ['title' => 'Mine', 'alias' => 'buttons', 'targets' => ['page']], [
            'btn_hyperlink_primary' => ['label' => 'My label', 'sorting' => 512],
        ]);

        $importer = new PresetImporter($this->connection);
        $plan = $importer->plan($this->fixture());

        self::assertFalse($plan->groups[0]['isNew']);
        self::assertFalse($plan->groups[0]['classes'][0]['isNew']);
        self::assertTrue($plan->groups[0]['classes'][1]['isNew']);
        self::assertTrue($plan->groups[1]['isNew']);
        self::assertSame(1, $plan->countNewGroups());
        self::assertSame(2, $plan->countNewClasses());

        self::assertSame(['groups' => 1, 'classes' => 2, 'updatedGroups' => 0, 'updatedClasses' => 0], $importer->apply($this->fixture()));

        // Existing group and class are untouched, the new class is appended
        $group = $this->connection->fetchAssociative('SELECT title, targets FROM tl_pcu_class_group WHERE id = ?', [$groupId]);
        self::assertSame(['title' => 'Mine', 'targets' => serialize(['page'])], $group);
        self::assertSame(
            [['cssClass' => 'btn_hyperlink_primary', 'label' => 'My label', 'sorting' => 512], ['cssClass' => 'btn_hyperlink_secondary', 'label' => 'Button sekundär', 'sorting' => 640]],
            array_map(
                static fn (array $row): array => ['cssClass' => $row['cssClass'], 'label' => $row['label'], 'sorting' => (int) $row['sorting']],
                $this->connection->fetchAllAssociative('SELECT cssClass, label, sorting FROM tl_pcu_class WHERE pid = ? ORDER BY sorting', [$groupId]),
            ),
        );
    }

    public function testRespectsSelection(): void
    {
        $importer = new PresetImporter($this->connection);

        self::assertSame(['groups' => 1, 'classes' => 1, 'updatedGroups' => 0, 'updatedClasses' => 0], $importer->apply($this->fixture(), ['buttons' => ['btn_hyperlink_secondary']]));
        self::assertSame(['btn_hyperlink_secondary'], $this->connection->fetchFirstColumn('SELECT cssClass FROM tl_pcu_class'));

        self::assertSame(['groups' => 1, 'classes' => 0, 'updatedGroups' => 0, 'updatedClasses' => 0], $importer->apply($this->fixture(), ['article-width' => []]));
        self::assertSame(2, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tl_pcu_class_group'));
    }

    public function testPlanShowsDifferencesOfExistingRecords(): void
    {
        $groupId = self::insertGroup($this->connection, ['title' => 'Old title', 'alias' => 'buttons', 'multiple' => 1, 'targets' => ['content', 'page'], 'contentTypes' => ['text']], [
            'btn_hyperlink_primary' => 'Old label',
            'btn_hyperlink_secondary' => ['label' => 'Button sekundär', 'published' => 0],
        ]);
        self::insertGroup($this->connection, ['title' => 'Artikelbreite', 'alias' => 'article-width', 'multiple' => 1, 'targets' => ['news', 'article'], 'published' => 0], [
            'full--width' => 'Volle Breite / ohne Abstand',
        ]);

        $plan = new PresetImporter($this->connection)->plan($this->fixture());
        [$buttons, $width] = $plan->groups;

        self::assertSame([
            ['field' => 'title', 'old' => 'Old title', 'new' => 'Button-Stil'],
            ['field' => 'multiple', 'old' => 'yes', 'new' => 'no'],
            ['field' => 'targets', 'old' => 'content: text; page', 'new' => 'content: hyperlink'],
        ], $buttons['changes']);
        self::assertSame([['field' => 'label', 'old' => 'Old label', 'new' => 'Button primär']], $buttons['classes'][0]['changes']);
        self::assertSame([], $buttons['classes'][1]['changes']);

        // Target order does not matter
        self::assertSame([], $width['changes']);
        self::assertSame([], $width['classes'][0]['changes']);

        self::assertSame(1, $plan->countChangedGroups());
        self::assertSame(1, $plan->countChangedClasses());
        self::assertTrue($plan->hasChanges());
        self::assertSame(0, $plan->countNewGroups() + $plan->countNewClasses());
        self::assertGreaterThan(0, $groupId);
    }

    public function testExistingRecordsStayUnchangedWithoutUpdates(): void
    {
        $groupId = self::insertGroup($this->connection, ['title' => 'Old title', 'alias' => 'buttons', 'targets' => ['page']], ['btn_hyperlink_primary' => 'Old label']);

        $result = new PresetImporter($this->connection)->apply($this->fixture());

        self::assertSame(['groups' => 1, 'classes' => 2, 'updatedGroups' => 0, 'updatedClasses' => 0], $result);
        self::assertSame('Old title', $this->connection->fetchOne('SELECT title FROM tl_pcu_class_group WHERE id = ?', [$groupId]));
        self::assertSame('Old label', $this->connection->fetchOne("SELECT label FROM tl_pcu_class WHERE cssClass = 'btn_hyperlink_primary'"));
    }

    public function testUpdatesOnlyRequestedRecords(): void
    {
        $groupId = self::insertGroup($this->connection, ['title' => 'Old title', 'alias' => 'buttons', 'multiple' => 1, 'targets' => ['page'], 'published' => 0], [
            'btn_hyperlink_primary' => ['label' => 'Old label', 'sorting' => 512],
            'btn_hyperlink_secondary' => ['label' => 'Old secondary', 'published' => 1],
        ]);

        $result = new PresetImporter($this->connection)->apply(
            $this->fixture(),
            [],
            ['groups' => ['buttons'], 'classes' => ['buttons' => ['btn_hyperlink_secondary']]],
        );

        self::assertSame(['groups' => 0, 'classes' => 0, 'updatedGroups' => 1, 'updatedClasses' => 1], $result);

        $group = $this->connection->fetchAssociative('SELECT * FROM tl_pcu_class_group WHERE id = ?', [$groupId]);
        self::assertIsArray($group);
        self::assertSame('Button-Stil', $group['title']);
        self::assertSame('buttons', $group['alias']);
        self::assertSame(0, (int) $group['multiple']);
        self::assertSame(1, (int) $group['published']);
        self::assertSame(serialize(['content']), $group['targets']);
        self::assertSame(serialize(['hyperlink']), $group['contentTypes']);

        $classes = $this->connection->fetchAllAssociativeIndexed('SELECT cssClass, label, published, sorting FROM tl_pcu_class WHERE pid = ?', [$groupId]);
        self::assertSame('Old label', $classes['btn_hyperlink_primary']['label']);
        self::assertSame(512, (int) $classes['btn_hyperlink_primary']['sorting']);
        self::assertSame('Button sekundär', $classes['btn_hyperlink_secondary']['label']);
        self::assertSame(0, (int) $classes['btn_hyperlink_secondary']['published']);

        // The empty selection imported nothing new
        self::assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tl_pcu_class_group'));
        self::assertSame([], new PresetImporter($this->connection)->plan($this->fixture())->groups[0]['changes']);
    }

    public function testUpdateRequestForUnchangedOrUnknownRecordsIsIgnored(): void
    {
        $importer = new PresetImporter($this->connection);
        $importer->apply($this->fixture());

        self::assertSame(
            ['groups' => 0, 'classes' => 0, 'updatedGroups' => 0, 'updatedClasses' => 0],
            $importer->apply($this->fixture(), null, ['groups' => ['buttons', 'unknown'], 'classes' => ['buttons' => ['btn_hyperlink_primary', 'nope']]]),
        );
    }

    public function testRollsBackOnFailure(): void
    {
        $this->connection->executeStatement('CREATE TRIGGER fail BEFORE INSERT ON tl_pcu_class WHEN NEW.cssClass = \'full--width\' BEGIN SELECT RAISE(ABORT, \'boom\'); END');

        try {
            new PresetImporter($this->connection)->apply($this->fixture());
            self::fail('Expected an exception.');
        } catch (\Throwable) {
        }

        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tl_pcu_class_group'));
        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tl_pcu_class'));
    }

    private function fixture(): Preset
    {
        return PresetSerializer::fromJson((string) file_get_contents(__DIR__.'/../Fixtures/preset.json'));
    }
}
