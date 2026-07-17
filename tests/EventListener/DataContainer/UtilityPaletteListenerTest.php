<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\EventListener\DataContainer;

use Contao\DataContainer;
use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\EventListener\DataContainer\UtilityPaletteListener;

final class UtilityPaletteListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testAppendsUtilityLegendAndFieldsToAllPalettesOfTheGivenTable(): void
    {
        $GLOBALS['TL_DCA']['tl_content'] = [
            'palettes' => [
                '__selector__' => ['addImage'],
                'text' => '{type_legend},type;{expert_legend:hide},cssID',
                'image' => '{type_legend},type;{expert_legend:hide},cssID',
            ],
        ];

        $dc = $this->createMock(DataContainer::class);
        $dc->method('__get')->willReturnMap([
            ['table', 'tl_content'],
        ]);

        (new UtilityPaletteListener())($dc);

        foreach (['text', 'image'] as $palette) {
            $value = $GLOBALS['TL_DCA']['tl_content']['palettes'][$palette];

            self::assertStringContainsString('{utility_legend:hide}', $value);
            self::assertStringContainsString('utilityMt,utilityMb,utilityPt,utilityPb', $value);
        }

        self::assertSame(['addImage'], $GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__']);
    }

    public function testDoesNotTouchOtherTables(): void
    {
        $GLOBALS['TL_DCA']['tl_article'] = [
            'palettes' => [
                'default' => '{title_legend},title;{expert_legend:hide},cssID',
            ],
        ];

        $dc = $this->createMock(DataContainer::class);
        $dc->method('__get')->willReturnMap([
            ['table', 'tl_form_field'],
        ]);

        (new UtilityPaletteListener())($dc);

        self::assertSame(
            '{title_legend},title;{expert_legend:hide},cssID',
            $GLOBALS['TL_DCA']['tl_article']['palettes']['default'],
        );
    }

    public function testDoesNothingWhenDataContainerIsNull(): void
    {
        $GLOBALS['TL_DCA']['tl_content'] = [
            'palettes' => [
                'text' => '{type_legend},type;{expert_legend:hide},cssID',
            ],
        ];

        (new UtilityPaletteListener())(null);

        self::assertSame(
            '{type_legend},type;{expert_legend:hide},cssID',
            $GLOBALS['TL_DCA']['tl_content']['palettes']['text'],
        );
    }
}
