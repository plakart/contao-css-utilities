<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\EventListener;

use Contao\LayoutModel;
use Contao\PageModel;
use Contao\PageRegular;
use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\EventListener\GeneratePageListener;

final class GeneratePageListenerTest extends TestCase
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
        $this->invokeListener('');

        self::assertSame(['bundles/plakartcssutilities/css/utilities.css|static'], $GLOBALS['TL_CSS']);
    }

    public function testSkipsStylesheetWhenLayoutDisablesIt(): void
    {
        $this->invokeListener('1');

        self::assertArrayNotHasKey('TL_CSS', $GLOBALS);
    }

    private function invokeListener(string $disableUtilityCss): void
    {
        $layout = $this->createMock(LayoutModel::class);
        $layout->method('__get')->with('disableUtilityCss')->willReturn($disableUtilityCss);

        (new GeneratePageListener())(
            $this->createMock(PageModel::class),
            $layout,
            $this->createMock(PageRegular::class),
        );
    }
}
