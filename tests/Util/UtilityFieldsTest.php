<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\Util\UtilityFields;

final class UtilityFieldsTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testRegistersFieldsOnly(): void
    {
        $GLOBALS['TL_DCA']['tl_content'] = [
            'palettes' => [
                '__selector__' => ['addImage'],
                'text' => '{type_legend},type;{expert_legend:hide},cssID',
                'image' => '{type_legend},type;{expert_legend:hide},cssID',
            ],
            'fields' => [],
        ];

        UtilityFields::register('tl_content');

        foreach (['utilityMt', 'utilityMb', 'utilityPt', 'utilityPb'] as $field) {
            self::assertArrayHasKey($field, $GLOBALS['TL_DCA']['tl_content']['fields']);
            self::assertTrue($GLOBALS['TL_DCA']['tl_content']['fields'][$field]['exclude']);
            self::assertSame('select', $GLOBALS['TL_DCA']['tl_content']['fields'][$field]['inputType']);
            self::assertSame(
                ['0', '1', '2', '3', '4', '5', '6', '7', '8'],
                $GLOBALS['TL_DCA']['tl_content']['fields'][$field]['options'],
            );
            self::assertSame(
                ['type' => 'string', 'length' => 2, 'default' => ''],
                $GLOBALS['TL_DCA']['tl_content']['fields'][$field]['sql'],
            );
        }

        // Palettes must remain untouched - that is now the listener's job.
        self::assertSame(
            '{type_legend},type;{expert_legend:hide},cssID',
            $GLOBALS['TL_DCA']['tl_content']['palettes']['text'],
        );
        self::assertSame(
            '{type_legend},type;{expert_legend:hide},cssID',
            $GLOBALS['TL_DCA']['tl_content']['palettes']['image'],
        );
        self::assertSame(['addImage'], $GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__']);
    }
}
