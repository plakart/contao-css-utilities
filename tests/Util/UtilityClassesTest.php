<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\Util\UtilityClasses;

final class UtilityClassesTest extends TestCase
{
    public function testDefinesFourPropertiesAndNineSteps(): void
    {
        self::assertSame(['mt', 'mb', 'pt', 'pb'], array_keys(UtilityClasses::PROPERTIES));
        self::assertSame(range(0, 8), UtilityClasses::steps());
        self::assertSame(range(1, 8), array_keys(UtilityClasses::SCALE));
    }

    public function testAllContainsExactly36UniqueClasses(): void
    {
        $all = UtilityClasses::all();

        self::assertCount(36, $all);
        self::assertSame($all, array_unique($all));
        self::assertContains('mt-0', $all);
        self::assertContains('mb-3', $all);
        self::assertContains('pt-5', $all);
        self::assertContains('pb-8', $all);
    }

    public function testGeneratedCssContainsCustomPropertiesAndRules(): void
    {
        $css = UtilityClasses::generateCss();

        self::assertStringContainsString('--space-4: clamp(1rem, 0.8rem + 1vw, 2rem);', $css);
        self::assertStringContainsString('.mt-0 { margin-top: 0 !important; }', $css);
        self::assertStringContainsString('.pb-8 { padding-bottom: var(--space-8) !important; }', $css);
        self::assertSame(36, preg_match_all('/^\.[a-z]{2}-\d \{ [a-z-]+: [^;]+ !important; \}$/m', $css));
    }
}
