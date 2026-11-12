<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\EventListener;

use Contao\CoreBundle\Event\LayoutEvent;
use Contao\LayoutModel;
use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\EventListener\LayoutListener;

final class LayoutListenerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        unset($GLOBALS['TL_CSS']);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_CSS']);

        parent::tearDown();
    }

    public function testAddsStylesheetByDefault(): void
    {
        $this->invokeListener($this->mockLayout(''));

        self::assertSame(['bundles/plakartcssutilities/css/utilities.css|static'], $GLOBALS['TL_CSS']);
    }

    public function testAddsStylesheetWithoutLayout(): void
    {
        $this->invokeListener(null);

        self::assertSame(['bundles/plakartcssutilities/css/utilities.css|static'], $GLOBALS['TL_CSS']);
    }

    public function testSkipsStylesheetWhenLayoutDisablesIt(): void
    {
        $this->invokeListener($this->mockLayout('1'));

        self::assertArrayNotHasKey('TL_CSS', $GLOBALS);
    }

    private function mockLayout(string $disableUtilityCss): LayoutModel
    {
        $layout = $this->createMock(LayoutModel::class);
        $layout->method('__get')->with('disableUtilityCss')->willReturn($disableUtilityCss);

        return $layout;
    }

    private function invokeListener(?LayoutModel $layout): void
    {
        // LayoutTemplate is final, so the event itself is mocked
        $event = $this->createMock(LayoutEvent::class);
        $event->method('getLayout')->willReturn($layout);

        (new LayoutListener())($event);
    }
}
