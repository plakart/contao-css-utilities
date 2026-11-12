<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\Widget;

use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Plakart\CssUtilitiesBundle\ClassSelection\ClassGroup;
use Plakart\CssUtilitiesBundle\Widget\ClassSelectionWidget;

final class ClassSelectionWidgetTest extends ContaoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        System::setContainer($this->getContainerWithContaoConfiguration());
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_CSS']);

        parent::tearDown();
    }

    public function testRendersOneSelectPerGroup(): void
    {
        $html = $this->createWidget(serialize(['buttons' => ['btn-b'], 'spacing' => ['s1', 's2']]))->generate();

        self::assertStringContainsString('<label for="ctrl_pcuClasses_buttons">Buttons &amp; links</label>', $html);
        self::assertStringContainsString('<select name="pcuClasses[buttons]" id="ctrl_pcuClasses_buttons" class="tl_select"', $html);
        self::assertStringContainsString('<option value="">-</option>', $html);
        self::assertStringContainsString('<option value="btn-a">A</option>', $html);
        self::assertStringContainsString('<option value="btn-b" selected>B</option>', $html);

        self::assertStringContainsString('<input type="hidden" name="pcuClasses[spacing]" value="">', $html);
        self::assertStringContainsString('data-controller="contao--choices"', $html);
        self::assertStringContainsString('<select name="pcuClasses[spacing][]" id="ctrl_pcuClasses_spacing" class="tl_mselect" multiple', $html);
        self::assertStringContainsString('<option value="s1" selected>S1</option><option value="s2" selected>S2</option>', $html);

        self::assertSame([ClassSelectionWidget::STYLESHEET], $GLOBALS['TL_CSS']);
    }

    public function testEscapesLabelsAndClasses(): void
    {
        $widget = $this->createWidget('');
        $widget->setGroups([new ClassGroup(1, 'x', '<b>X</b>', false, [], ['a"b' => '<i>label</i>'])]);

        $html = $widget->generate();

        self::assertStringContainsString('&lt;b&gt;X&lt;/b&gt;', $html);
        self::assertStringContainsString('value="a&quot;b"', $html);
        self::assertStringContainsString('&lt;i&gt;label&lt;/i&gt;', $html);
    }

    public function testValidatorNormalizesAndSerializes(): void
    {
        $widget = $this->createWidget('');

        self::assertSame(
            serialize(['buttons' => ['btn-a'], 'spacing' => ['s2']]),
            $widget->callValidator(['buttons' => 'btn-a', 'spacing' => ['s2', 'unknown'], 'other' => 'x']),
        );
        self::assertSame('', $widget->callValidator(['buttons' => '', 'spacing' => '']));
    }

    private function createWidget(string $value): TestableClassSelectionWidget
    {
        $widget = new TestableClassSelectionWidget(['id' => 'pcuClasses', 'name' => 'pcuClasses', 'value' => $value]);
        $widget->setGroups([
            new ClassGroup(1, 'buttons', 'Buttons & links', false, [], ['btn-a' => 'A', 'btn-b' => 'B']),
            new ClassGroup(2, 'spacing', 'Spacing', true, [], ['s1' => 'S1', 's2' => 'S2']),
        ]);

        return $widget;
    }
}

final class TestableClassSelectionWidget extends ClassSelectionWidget
{
    public function callValidator(mixed $input): string
    {
        return $this->validator($input);
    }
}
