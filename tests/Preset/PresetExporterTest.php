<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\Preset;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\Preset\Preset;
use Plakart\CssUtilitiesBundle\Preset\PresetExporter;
use Plakart\CssUtilitiesBundle\Preset\PresetGroup;
use Plakart\CssUtilitiesBundle\Tests\DatabaseTestTrait;

final class PresetExporterTest extends TestCase
{
    use DatabaseTestTrait;

    private Connection $connection;

    private int $buttons;

    private int $width;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = self::createConnection();

        $this->width = self::insertGroup($this->connection, ['title' => 'Width', 'alias' => 'width', 'multiple' => 1, 'targets' => ['article', 'news'], 'published' => 0], [
            'full--width' => 'Full',
        ]);
        $this->buttons = self::insertGroup($this->connection, ['title' => 'Buttons', 'alias' => 'buttons', 'targets' => ['content', 'module'], 'contentTypes' => ['hyperlink'], 'moduleTypes' => ['navigation']], [
            'btn-primary' => 'Primary',
            'btn-secondary' => ['label' => 'Secondary', 'published' => 0],
        ]);
    }

    public function testExportsEverything(): void
    {
        $preset = new PresetExporter($this->connection)->export();

        self::assertSame(['buttons', 'width'], self::aliases($preset));

        [$buttons, $width] = $preset->groups;

        self::assertSame('Buttons', $buttons->title);
        self::assertFalse($buttons->multiple);
        self::assertTrue($buttons->published);
        self::assertSame(['content' => ['hyperlink'], 'module' => ['navigation']], $buttons->targets);
        self::assertSame('btn-primary', $buttons->classes[0]->class);
        self::assertTrue($buttons->classes[0]->published);
        self::assertSame('btn-secondary', $buttons->classes[1]->class);
        self::assertFalse($buttons->classes[1]->published);

        self::assertTrue($width->multiple);
        self::assertFalse($width->published);
        self::assertSame(['article' => [], 'news' => []], $width->targets);
    }

    public function testExportsSelectedGroups(): void
    {
        $preset = new PresetExporter($this->connection)->export([$this->width]);

        self::assertSame(['width'], self::aliases($preset));
        self::assertCount(1, $preset->groups[0]->classes);
    }

    public function testExportsSelectedClassesWithTheirGroups(): void
    {
        $classId = (int) $this->connection->fetchOne("SELECT id FROM tl_pcu_class WHERE cssClass = 'btn-secondary'");

        $preset = new PresetExporter($this->connection)->export([$this->width], [$classId]);

        self::assertSame(['buttons', 'width'], self::aliases($preset));
        self::assertSame('btn-secondary', $preset->groups[0]->classes[0]->class);
        self::assertCount(1, $preset->groups[0]->classes);
        self::assertSame([], $preset->groups[1]->classes);
    }

    public function testEmptySelection(): void
    {
        self::assertSame([], new PresetExporter($this->connection)->export([], [])->groups);
    }

    /**
     * @return list<string>
     */
    private static function aliases(Preset $preset): array
    {
        return array_map(static fn (PresetGroup $group): string => $group->alias, $preset->groups);
    }
}
