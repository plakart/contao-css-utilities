<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\Target;

use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\Target\TargetRegistry;

final class TargetRegistryTest extends TestCase
{
    public function testCoreTargetsWithoutOptionalBundles(): void
    {
        $registry = new TargetRegistry(['ContaoCoreBundle' => 'Contao\CoreBundle\ContaoCoreBundle']);

        self::assertSame(['content', 'article', 'form_field', 'module', 'page'], array_keys($registry->all()));
        self::assertFalse($registry->has('news'));
        self::assertNull($registry->forTable('tl_news'));
        self::assertNull($registry->forTable('tl_calendar_events'));
    }

    public function testOptionalTargetsWithInstalledBundles(): void
    {
        $registry = new TargetRegistry([
            'ContaoCoreBundle' => 'Contao\CoreBundle\ContaoCoreBundle',
            'ContaoNewsBundle' => 'Contao\NewsBundle\ContaoNewsBundle',
            'ContaoCalendarBundle' => 'Contao\CalendarBundle\ContaoCalendarBundle',
        ]);

        self::assertSame(
            ['content', 'article', 'form_field', 'module', 'page', 'news', 'event'],
            array_keys($registry->all()),
        );
        self::assertSame('event', $registry->forTable('tl_calendar_events')?->key);
    }

    public function testTargetDefinitions(): void
    {
        $registry = new TargetRegistry([]);

        $content = $registry->get('content');
        self::assertSame('tl_content', $content->table);
        self::assertSame('cssID', $content->column);
        self::assertTrue($content->isCssId());
        self::assertSame('type', $content->typeField);

        $formField = $registry->get('form_field');
        self::assertSame('tl_form_field', $formField->table);
        self::assertSame('class', $formField->column);
        self::assertFalse($formField->isCssId());
        self::assertSame('type', $formField->typeField);

        self::assertSame('type', $registry->get('module')->typeField);
        self::assertNull($registry->get('article')->typeField);

        $page = $registry->get('page');
        self::assertSame('cssClass', $page->column);
        self::assertNull($page->typeField);
    }

    public function testUnknownTargetThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TargetRegistry([])->get('news');
    }
}
