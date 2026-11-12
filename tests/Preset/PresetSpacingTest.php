<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\Preset;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\Migration\PresetFileMigration;
use Plakart\CssUtilitiesBundle\Preset\InvalidPresetException;
use Plakart\CssUtilitiesBundle\Preset\Preset;
use Plakart\CssUtilitiesBundle\Preset\PresetExporter;
use Plakart\CssUtilitiesBundle\Preset\PresetImporter;
use Plakart\CssUtilitiesBundle\Preset\PresetSerializer;
use Plakart\CssUtilitiesBundle\Preset\PresetSpacingStep;
use Plakart\CssUtilitiesBundle\Tests\DatabaseTestTrait;

final class PresetSpacingTest extends TestCase
{
    use DatabaseTestTrait;

    private const JSON = <<<'JSON'
        {
            "format": "plakart-css-classes",
            "version": 1,
            "groups": [],
            "spacing": [
                {
                    "step": 13,
                    "label": "Hero"
                },
                {
                    "step": 16
                }
            ]
        }

        JSON;

    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = self::createConnection();
    }

    public function testParsesAndWritesSpacing(): void
    {
        $preset = PresetSerializer::fromJson(self::JSON);

        self::assertEquals([new PresetSpacingStep(13, 'Hero'), new PresetSpacingStep(16, '')], $preset->spacing);
        self::assertFalse($preset->isEmpty());
        self::assertSame(self::JSON, PresetSerializer::toJson($preset));
    }

    public function testSortsStepsAndOmitsEmptySpacing(): void
    {
        $preset = PresetSerializer::fromJson('{"format":"plakart-css-classes","version":1,"spacing":[{"step":20},{"step":14}]}');

        self::assertSame([14, 20], array_map(static fn (PresetSpacingStep $step): int => $step->step, $preset->spacing));
        self::assertStringNotContainsString('spacing', PresetSerializer::toJson(new Preset([])));
    }

    #[DataProvider('provideInvalidSpacing')]
    public function testRejectsInvalidSpacing(string $spacing, string $message): void
    {
        $this->expectException(InvalidPresetException::class);
        $this->expectExceptionMessage($message);

        PresetSerializer::fromJson('{"format":"plakart-css-classes","version":1,"spacing":'.$spacing.'}');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideInvalidSpacing(): iterable
    {
        yield 'not a list' => ['{"a":1}', 'spacing: Expected a list'];
        yield 'not an object' => ['[13]', 'spacing[0]: Expected an object'];
        yield 'fixed step' => ['[{"step":12}]', 'spacing[0].step: Expected a whole number from 13'];
        yield 'string step' => ['[{"step":"13"}]', 'spacing[0].step: Expected a whole number from 13'];
        yield 'float step' => ['[{"step":13.5}]', 'spacing[0].step: Expected a whole number from 13'];
        yield 'duplicate' => ['[{"step":13},{"step":13}]', 'spacing[1].step: Duplicate step 13'];
        yield 'label not string' => ['[{"step":13,"label":5}]', 'spacing[0].label: Expected a string'];
    }

    public function testExport(): void
    {
        $this->connection->insert('tl_pcu_spacing', ['step' => 16, 'label' => '']);
        $this->connection->insert('tl_pcu_spacing', ['step' => 13, 'label' => 'Hero']);

        $exporter = new PresetExporter($this->connection);

        self::assertSame(self::JSON, PresetSerializer::toJson($exporter->export()));
        self::assertEquals([new PresetSpacingStep(16)], $exporter->export([], [], [16])->spacing);
        self::assertSame([], $exporter->export([], [], [])->spacing);
    }

    public function testPlanAndApply(): void
    {
        $this->connection->insert('tl_pcu_spacing', ['step' => 13, 'label' => 'Old']);

        $importer = new PresetImporter($this->connection);
        $plan = $importer->plan(PresetSerializer::fromJson(self::JSON));

        self::assertFalse($plan->spacing[0]['isNew']);
        self::assertSame([['field' => 'label', 'old' => 'Old', 'new' => 'Hero']], $plan->spacing[0]['changes']);
        self::assertTrue($plan->spacing[1]['isNew']);
        self::assertSame(1, $plan->countNewSpacing());
        self::assertSame(1, $plan->countChangedSpacing());
        self::assertTrue($plan->hasChanges());

        // Without update request the label stays, the new step is created
        $result = $importer->apply(PresetSerializer::fromJson(self::JSON));
        self::assertSame(1, $result['spacing']);
        self::assertSame(0, $result['updatedSpacing']);
        self::assertSame([13 => 'Old', 16 => ''], $this->steps());

        $result = $importer->apply(PresetSerializer::fromJson(self::JSON), [], ['spacing' => [13]], []);
        self::assertSame(0, $result['spacing']);
        self::assertSame(1, $result['updatedSpacing']);
        self::assertSame([13 => 'Hero', 16 => ''], $this->steps());
    }

    public function testRespectsSpacingSelection(): void
    {
        new PresetImporter($this->connection)->apply(PresetSerializer::fromJson(self::JSON), null, null, [16]);

        self::assertSame([16 => ''], $this->steps());
    }

    public function testMigrationImportsSpacingOnlyPresetOnce(): void
    {
        $file = sys_get_temp_dir().'/pcu-spacing-'.bin2hex(random_bytes(4)).'.json';
        file_put_contents($file, self::JSON);

        try {
            $migration = new PresetFileMigration($this->connection, new PresetImporter($this->connection), $file);

            self::assertTrue($migration->shouldRun());
            self::assertStringContainsString('2 additional spacing steps', (string) $migration->run()->getMessage());
            self::assertFalse($migration->shouldRun());
        } finally {
            unlink($file);
        }
    }

    /**
     * @return array<int, string>
     */
    private function steps(): array
    {
        $steps = [];

        foreach ($this->connection->fetchAllAssociative('SELECT step, label FROM tl_pcu_spacing ORDER BY step') as $row) {
            $steps[(int) $row['step']] = (string) $row['label'];
        }

        return $steps;
    }
}
