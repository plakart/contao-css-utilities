<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\Util\UtilityClassMerger;

final class UtilityClassMergerTest extends TestCase
{
    public function testAddsSelectedClassesToEmptyString(): void
    {
        self::assertSame(
            'mt-4 pb-2',
            UtilityClassMerger::merge('', ['mt' => '4', 'mb' => '', 'pt' => '', 'pb' => '2']),
        );
    }

    public function testStepZeroIsAValidSelection(): void
    {
        self::assertSame(
            'mt-0',
            UtilityClassMerger::merge('', ['mt' => '0', 'mb' => '', 'pt' => '', 'pb' => '']),
        );
    }

    public function testReplacesPreviousUtilityClassesAndKeepsForeignOnes(): void
    {
        self::assertSame(
            'block headline mt-4',
            UtilityClassMerger::merge('block mt-2 headline', ['mt' => '4', 'mb' => '', 'pt' => '', 'pb' => '']),
        );
    }

    public function testIsIdempotent(): void
    {
        $selection = ['mt' => '1', 'mb' => '8', 'pt' => '', 'pb' => ''];
        $once = UtilityClassMerger::merge('foo', $selection);

        self::assertSame($once, UtilityClassMerger::merge($once, $selection));
    }

    public function testEmptySelectionRemovesAllUtilityClasses(): void
    {
        self::assertSame(
            'foo',
            UtilityClassMerger::merge('mt-3 foo pb-0', ['mt' => '', 'mb' => '', 'pt' => '', 'pb' => '']),
        );
    }

    public function testUnknownLookalikeClassesArePreserved(): void
    {
        self::assertSame(
            'mt-99 mx-4 mt-2',
            UtilityClassMerger::merge('mt-99 mx-4', ['mt' => '2', 'mb' => '', 'pt' => '', 'pb' => '']),
        );
    }

    public function testNormalizesWhitespace(): void
    {
        self::assertSame(
            'a b',
            UtilityClassMerger::merge("  a \t b  ", ['mt' => '', 'mb' => '', 'pt' => '', 'pb' => '']),
        );
    }
}
