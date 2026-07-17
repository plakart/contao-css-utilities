<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Util;

use Contao\CoreBundle\DataContainer\PaletteManipulator;

final class UtilityFields
{
    public const FIELDS = ['utilityMt', 'utilityMb', 'utilityPt', 'utilityPb'];

    /**
     * Adds the four utility select fields and the utility_legend to every palette of the table.
     */
    public static function register(string $table): void
    {
        $options = array_map(strval(...), UtilityClasses::steps());

        foreach (self::FIELDS as $field) {
            $GLOBALS['TL_DCA'][$table]['fields'][$field] = [
                'exclude' => true,
                'inputType' => 'select',
                'options' => $options,
                'eval' => ['includeBlankOption' => true, 'tl_class' => 'w25'],
                'sql' => ['type' => 'string', 'length' => 2, 'default' => ''],
            ];
        }

        $manipulator = PaletteManipulator::create()
            ->addLegend('utility_legend', 'expert_legend', PaletteManipulator::POSITION_BEFORE, true)
            ->addField(self::FIELDS, 'utility_legend', PaletteManipulator::POSITION_APPEND)
        ;

        foreach ($GLOBALS['TL_DCA'][$table]['palettes'] ?? [] as $name => $palette) {
            if (!\is_string($palette)) {
                continue;
            }

            $manipulator->applyToPalette($name, $table);
        }
    }
}
