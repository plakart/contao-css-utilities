<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\Util;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\Util\ClassAttributeMerger;

final class ClassAttributeMergerTest extends TestCase
{
    /**
     * @param list<string> $strip
     * @param list<string> $add
     */
    #[DataProvider('provideMergeCases')]
    public function testMerge(string $current, array $strip, array $add, string $expected): void
    {
        self::assertSame($expected, ClassAttributeMerger::merge($current, $strip, $add));
    }

    /**
     * @return iterable<string, array{string, list<string>, list<string>, string}>
     */
    public static function provideMergeCases(): iterable
    {
        yield 'keeps foreign classes' => ['foo bar', ['baz'], [], 'foo bar'];
        yield 'strips known classes' => ['foo btn-primary bar', ['btn-primary'], [], 'foo bar'];
        yield 'appends selection' => ['foo', ['btn-primary'], ['btn-primary'], 'foo btn-primary'];
        yield 'replaces previous selection' => ['foo full--width', ['full--width', 'boxed'], ['boxed'], 'foo boxed'];
        yield 'splits multi-token strip entries' => ['btn btn-primary foo', ['btn btn-primary'], [], 'foo'];
        yield 'splits multi-token additions' => ['foo', [], ['btn btn-primary'], 'foo btn btn-primary'];
        yield 'deduplicates' => ['foo foo', [], ['foo', 'bar', 'bar'], 'foo bar'];
        yield 'normalizes whitespace' => ["  foo \t bar\n", [], [' baz '], 'foo bar baz'];
        yield 'empty input' => ['', ['foo'], [], ''];
        yield 'empty input with selection' => ['', [], ['foo'], 'foo'];
    }
}
