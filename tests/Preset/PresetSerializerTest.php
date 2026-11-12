<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\Preset;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\Preset\InvalidPresetException;
use Plakart\CssUtilitiesBundle\Preset\PresetSerializer;

final class PresetSerializerTest extends TestCase
{
    public function testParsesFixture(): void
    {
        $preset = PresetSerializer::fromJson((string) file_get_contents(__DIR__.'/../Fixtures/preset.json'));

        self::assertCount(2, $preset->groups);
        self::assertSame(3, $preset->countClasses());

        [$buttons, $width] = $preset->groups;

        self::assertSame('buttons', $buttons->alias);
        self::assertSame('Button-Stil', $buttons->title);
        self::assertFalse($buttons->multiple);
        self::assertTrue($buttons->published);
        self::assertSame(['content' => ['hyperlink']], $buttons->targets);
        self::assertSame('btn_hyperlink_primary', $buttons->classes[0]->class);
        self::assertSame('Button primär', $buttons->classes[0]->label);
        self::assertTrue($buttons->classes[0]->published);
        self::assertFalse($buttons->classes[1]->published);

        self::assertTrue($width->multiple);
        self::assertFalse($width->published);
        self::assertSame(['article' => [], 'news' => []], $width->targets);
    }

    public function testRoundTrip(): void
    {
        $json = (string) file_get_contents(__DIR__.'/../Fixtures/preset.json');

        self::assertSame($json, PresetSerializer::toJson(PresetSerializer::fromJson($json)));
    }

    public function testDefaultsAndNormalization(): void
    {
        $preset = PresetSerializer::fromJson(json_encode([
            'format' => 'plakart-css-classes',
            'version' => 1,
            'groups' => [[
                'alias' => 'a',
                'title' => ' A ',
                'targets' => [],
                'classes' => [['class' => ' btn  btn-primary btn ', 'label' => 'B']],
            ]],
        ], \JSON_THROW_ON_ERROR));

        $group = $preset->groups[0];

        self::assertSame('A', $group->title);
        self::assertFalse($group->multiple);
        self::assertTrue($group->published);
        self::assertSame([], $group->targets);
        self::assertSame('btn btn-primary', $group->classes[0]->class);
    }

    public function testEmptyPreset(): void
    {
        self::assertSame([], PresetSerializer::fromJson('{"format":"plakart-css-classes","version":1,"groups":[]}')->groups);
        self::assertSame([], PresetSerializer::fromJson('{"format":"plakart-css-classes","version":1}')->groups);
    }

    #[DataProvider('provideInvalidPresets')]
    public function testRejectsInvalidPresets(string $json, string $message): void
    {
        $this->expectException(InvalidPresetException::class);
        $this->expectExceptionMessage($message);

        PresetSerializer::fromJson($json);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideInvalidPresets(): iterable
    {
        $wrap = static fn (array $groups): string => json_encode(['format' => 'plakart-css-classes', 'version' => 1, 'groups' => $groups], \JSON_THROW_ON_ERROR);
        $group = static fn (array $override = []): array => $override + ['alias' => 'a', 'title' => 'A', 'targets' => ['content' => []], 'classes' => [['class' => 'x', 'label' => 'X']]];

        yield 'invalid json' => ['{', 'Invalid JSON'];
        yield 'not an object' => ['"foo"', 'must be a JSON object'];
        yield 'wrong format' => ['{"format":"other","version":1}', 'format: Expected "plakart-css-classes"'];
        yield 'wrong version' => ['{"format":"plakart-css-classes","version":2}', 'version: Unsupported version'];
        yield 'groups not a list' => ['{"format":"plakart-css-classes","version":1,"groups":{"a":1}}', 'groups: Expected a list'];
        yield 'group not an object' => [$wrap(['x']), 'groups[0]: Expected an object'];
        yield 'missing alias' => [$wrap([$group(['alias' => ''])]), 'groups[0].alias: Required'];
        yield 'invalid alias' => [$wrap([$group(['alias' => 'Foo Bar'])]), 'groups[0].alias: Only a-z'];
        yield 'missing title' => [$wrap([$group(['title' => null])]), 'groups[0].title: Required'];
        yield 'duplicate alias' => [$wrap([$group(), $group()]), 'groups[1].alias: Duplicate alias "a"'];
        yield 'multiple not bool' => [$wrap([$group(['multiple' => 'yes'])]), 'groups[0].multiple: Expected true or false'];
        yield 'targets list' => [$wrap([$group(['targets' => ['content']])]), 'groups[0].targets: Expected an object'];
        yield 'types not list' => [$wrap([$group(['targets' => ['content' => 'text']])]), 'groups[0].targets.content: Expected a list'];
        yield 'types not strings' => [$wrap([$group(['targets' => ['content' => [1]]])]), 'groups[0].targets.content: Expected a list'];
        yield 'classes not list' => [$wrap([$group(['classes' => 'x'])]), 'groups[0].classes: Expected a list'];
        yield 'class not object' => [$wrap([$group(['classes' => ['x']])]), 'groups[0].classes[0]: Expected an object'];
        yield 'missing class' => [$wrap([$group(['classes' => [['label' => 'X']]])]), 'groups[0].classes[0].class: Required'];
        yield 'invalid class' => [$wrap([$group(['classes' => [['class' => 'ok 1bad', 'label' => 'X']]])]), 'groups[0].classes[0].class: "1bad" is not a valid'];
        yield 'missing label' => [$wrap([$group(['classes' => [['class' => 'x']]])]), 'groups[0].classes[0].label: Required'];
        yield 'duplicate class' => [$wrap([$group(['classes' => [['class' => 'x', 'label' => 'X'], ['class' => 'x', 'label' => 'Y']]])]), 'groups[0].classes[1].class: Duplicate class "x"'];
        yield 'published not bool' => [$wrap([$group(['classes' => [['class' => 'x', 'label' => 'X', 'published' => 1]]])]), 'groups[0].classes[0].published: Expected true or false'];
    }
}
