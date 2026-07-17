<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\EventListener\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Plakart\CssUtilitiesBundle\Util\UtilityFields;

/**
 * Applies the utility_legend and the four utility fields to every palette of a table.
 *
 * Runs as a config.onload callback (rather than at DCA-file load time) so that palettes
 * registered later by third-party bundles or app DCA files also receive the utility fields.
 */
class UtilityPaletteListener
{
    #[AsCallback('tl_content', 'config.onload')]
    #[AsCallback('tl_article', 'config.onload')]
    #[AsCallback('tl_form_field', 'config.onload')]
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
