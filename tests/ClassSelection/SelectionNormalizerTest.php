<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\ClassSelection;

use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\ClassSelection\ClassGroup;
use Plakart\CssUtilitiesBundle\ClassSelection\SelectionNormalizer;

final class SelectionNormalizerTest extends TestCase
{
    public function testKeepsValidSelection(): void
    {
        self::assertSame(
            ['buttons' => ['btn-a'], 'spacing' => ['s1', 's2']],
            SelectionNormalizer::normalize(['buttons' => 'btn-a', 'spacing' => ['s1', 's2']], self::classGroups()),
        );
    }

    public function testDropsUnknownGroupsAndClasses(): void
    {
        self::assertSame(
            ['spacing' => ['s1']],
            SelectionNormalizer::normalize(['unknown' => ['x'], 'buttons' => 'nope', 'spacing' => ['s1', 'nope']], self::classGroups()),
        );
    }

    public function testSingleChoiceKeepsFirstValue(): void
    {
        self::assertSame(['buttons' => ['btn-b']], SelectionNormalizer::normalize(['buttons' => ['btn-b', 'btn-a']], self::classGroups()));
    }

    public function testDeduplicatesAndFollowsGroupOrder(): void
    {
        self::assertSame(
            ['buttons' => ['btn-a'], 'spacing' => ['s2', 's1']],
            SelectionNormalizer::normalize(['spacing' => ['s2', 's1', 's2'], 'buttons' => 'btn-a'], self::classGroups()),
        );
    }

    public function testAcceptsSerializedInput(): void
    {
        self::assertSame(['buttons' => ['btn-a']], SelectionNormalizer::normalize(serialize(['buttons' => ['btn-a']]), self::classGroups()));
    }

    public function testGarbageInput(): void
    {
        self::assertSame([], SelectionNormalizer::normalize(null, self::classGroups()));
        self::assertSame([], SelectionNormalizer::normalize('', self::classGroups()));
        self::assertSame([], SelectionNormalizer::normalize('not serialized', self::classGroups()));
        self::assertSame([], SelectionNormalizer::normalize(42, self::classGroups()));
        self::assertSame([], SelectionNormalizer::normalize(['buttons' => ['' => ['nested']]], self::classGroups()));
    }

    public function testClasses(): void
    {
        self::assertSame(['btn-a', 's1', 's2'], SelectionNormalizer::classes(['buttons' => ['btn-a'], 'spacing' => ['s1', 's2']]));
        self::assertSame([], SelectionNormalizer::classes([]));
    }

    /**
     * @return list<ClassGroup>
     */
    private static function classGroups(): array
    {
        return [
            new ClassGroup(1, 'buttons', 'Buttons', false, ['content' => []], ['btn-a' => 'A', 'btn-b' => 'B']),
            new ClassGroup(2, 'spacing', 'Spacing', true, ['content' => []], ['s1' => 'S1', 's2' => 'S2']),
        ];
    }
}
