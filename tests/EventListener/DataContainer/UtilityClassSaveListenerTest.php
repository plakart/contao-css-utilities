<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\EventListener\DataContainer;

use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\EventListener\DataContainer\UtilityClassSaveListener;

final class UtilityClassSaveListenerTest extends TestCase
{
    public function testMergesSelectionIntoCssIdOfContentElement(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAssociative')
            ->willReturn([
                'cssID' => serialize(['hero', 'block mt-2']),
                'utilityMt' => '4',
                'utilityMb' => '',
                'utilityPt' => '0',
                'utilityPb' => '',
            ])
        ;
        $connection
            ->expects(self::once())
            ->method('update')
            ->with(
                'tl_content',
                ['cssID' => serialize(['hero', 'block mt-4 pt-0'])],
                ['id' => 5],
            )
        ;

        $dc = $this->createMock(DataContainer::class);
        $dc->method('__get')->willReturnMap([
            ['table', 'tl_content'],
            ['id', 5],
        ]);

        (new UtilityClassSaveListener($connection))->updateCssId($dc);
    }

    public function testHandlesEmptyCssIdColumn(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAssociative')
            ->willReturn([
                'cssID' => null,
                'utilityMt' => '',
                'utilityMb' => '2',
                'utilityPt' => '',
                'utilityPb' => '',
            ])
        ;
        $connection
            ->expects(self::once())
            ->method('update')
            ->with(
                'tl_article',
                ['cssID' => serialize(['', 'mb-2'])],
                ['id' => 7],
            )
        ;

        $dc = $this->createMock(DataContainer::class);
        $dc->method('__get')->willReturnMap([
            ['table', 'tl_article'],
            ['id', 7],
        ]);

        (new UtilityClassSaveListener($connection))->updateCssId($dc);
    }

    public function testMergesSelectionIntoFormFieldClass(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAssociative')
            ->willReturn([
                'class' => 'widget pb-1',
                'utilityMt' => '',
                'utilityMb' => '',
                'utilityPt' => '',
                'utilityPb' => '3',
            ])
        ;
        $connection
            ->expects(self::once())
            ->method('update')
            ->with(
                'tl_form_field',
                ['class' => 'widget pb-3'],
                ['id' => 9],
            )
        ;

        $dc = $this->createMock(DataContainer::class);
        $dc->method('__get')->willReturnMap([
            ['table', 'tl_form_field'],
            ['id', 9],
        ]);

        (new UtilityClassSaveListener($connection))->updateClass($dc);
    }

    public function testDoesNothingWhenRecordIsMissing(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAssociative')->willReturn(false);
        $connection->expects(self::never())->method('update');

        $dc = $this->createMock(DataContainer::class);
        $dc->method('__get')->willReturnMap([
            ['table', 'tl_content'],
            ['id', 1],
        ]);

        (new UtilityClassSaveListener($connection))->updateCssId($dc);
    }
}
