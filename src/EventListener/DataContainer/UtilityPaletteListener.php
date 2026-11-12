<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\EventListener\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\DataContainer;
use Plakart\CssUtilitiesBundle\Util\UtilityFields;

/**
 * Applies the utility_legend and the four utility fields to every palette of a table.
 *
 * Called by ClassSelectionPaletteListener (config.onload), so that palettes registered later
 * by third-party bundles or app DCA files also receive the utility fields.
 */
class UtilityPaletteListener
{
    public function __invoke(?DataContainer $dc = null): void
    {
        $table = $dc?->table;

        if (null === $table || '' === $table) {
            return;
        }

        $manipulator = PaletteManipulator::create()
            ->addLegend('utility_legend', 'expert_legend', PaletteManipulator::POSITION_BEFORE, true)
            ->addField(UtilityFields::FIELDS, 'utility_legend', PaletteManipulator::POSITION_APPEND)
        ;

        foreach ($GLOBALS['TL_DCA'][$table]['palettes'] ?? [] as $name => $palette) {
            if (!\is_string($palette)) {
                continue;
            }

            $manipulator->applyToPalette($name, $table);
        }
    }
}
