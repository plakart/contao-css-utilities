<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\Slug\Slug;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\EventListener\DataContainer\ClassGroupListener;
use Plakart\CssUtilitiesBundle\Target\TargetRegistry;

final class ClassGroupListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANG'], $GLOBALS['TL_CTE'], $GLOBALS['TL_FFL']);

        parent::tearDown();
    }

    public function testTargetOptionsContainOnlyInstalledTargets(): void
    {
        $GLOBALS['TL_LANG']['tl_pcu_class_group']['targetOptions'] = ['content' => 'Content elements'];

        $listener = new ClassGroupListener(
            new TargetRegistry([]),
            $this->createStub(Connection::class),
            $this->createStub(Slug::class),
        );

        self::assertSame(
            ['content' => 'Content elements', 'article' => 'article', 'form_field' => 'form_field', 'module' => 'module', 'page' => 'page'],
            $listener->targetOptions(),
        );
    }

    public function testGroupedTypeOptions(): void
    {
        $registry = [
            'texts' => ['headline' => 'A', 'text' => 'B'],
            'links' => ['hyperlink' => 'C'],
            'empty' => [],
        ];

        $labels = [
            'texts' => 'Text elements',
            'headline' => ['Headline', 'Description'],
            'hyperlink' => ['Hyperlink'],
        ];

        self::assertSame(
            [
                'Text elements' => ['headline' => 'Headline', 'text' => 'text'],
                'links' => ['hyperlink' => 'Hyperlink'],
            ],
            ClassGroupListener::groupedTypeOptions($registry, $labels),
        );
    }

    public function testFormFieldTypeOptions(): void
    {
        $GLOBALS['TL_FFL'] = ['text' => 'A', 'submit' => 'B'];
        $GLOBALS['TL_LANG']['FFL']['text'] = ['Text field', ''];

        $listener = new ClassGroupListener(
            new TargetRegistry([]),
            $this->createStub(Connection::class),
            $this->createStub(Slug::class),
        );

        self::assertSame(['text' => 'Text field', 'submit' => 'submit'], $listener->formFieldTypeOptions());
    }

    public function testAliasValidation(): void
    {
        self::assertTrue(ClassGroupListener::isValidAlias('buttons'));
        self::assertTrue(ClassGroupListener::isValidAlias('article-width_2'));
        self::assertFalse(ClassGroupListener::isValidAlias('Buttons'));
        self::assertFalse(ClassGroupListener::isValidAlias('foo bar'));
        self::assertFalse(ClassGroupListener::isValidAlias(''));
    }
}
