<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\EventListener\DataContainer\ClassAttributeListener;
use Plakart\CssUtilitiesBundle\EventListener\DataContainer\ClassSelectionPaletteListener;
use Plakart\CssUtilitiesBundle\EventListener\LoadDataContainerListener;
use Plakart\CssUtilitiesBundle\Target\TargetRegistry;

final class LoadDataContainerListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testAddsFieldAndCallbacksToTargetTables(): void
    {
        $GLOBALS['TL_DCA']['tl_page'] = ['config' => ['onload_callback' => [['core', 'onload']]], 'fields' => []];

        (new LoadDataContainerListener(new TargetRegistry([])))('tl_page');

        $dca = $GLOBALS['TL_DCA']['tl_page'];

        self::assertSame('pcuClassSelect', $dca['fields']['pcuClasses']['inputType']);
        self::assertTrue($dca['fields']['pcuClasses']['exclude']);
        self::assertSame('blob', $dca['fields']['pcuClasses']['sql']['type']);
        self::assertSame([['core', 'onload'], [ClassSelectionPaletteListener::class, '__invoke']], $dca['config']['onload_callback']);
        self::assertSame([[ClassAttributeListener::class, '__invoke']], $dca['config']['onsubmit_callback']);
    }

    public function testIgnoresOtherTablesAndMissingBundles(): void
    {
        $GLOBALS['TL_DCA']['tl_files'] = ['fields' => []];
        $GLOBALS['TL_DCA']['tl_news'] = ['fields' => []];

        $listener = new LoadDataContainerListener(new TargetRegistry([]));
        $listener('tl_files');
        $listener('tl_news');

        self::assertSame(['fields' => []], $GLOBALS['TL_DCA']['tl_files']);
        self::assertSame(['fields' => []], $GLOBALS['TL_DCA']['tl_news']);
    }

    public function testAddsNewsFieldWithNewsBundle(): void
    {
        $GLOBALS['TL_DCA']['tl_news'] = ['fields' => []];

        (new LoadDataContainerListener(new TargetRegistry(['ContaoNewsBundle' => 'X'])))('tl_news');

        self::assertArrayHasKey('pcuClasses', $GLOBALS['TL_DCA']['tl_news']['fields']);
    }
}
