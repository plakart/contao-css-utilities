<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\StringUtil;
use Plakart\CssUtilitiesBundle\Util\UtilityClasses;

/**
 * DCA callbacks of tl_pcu_spacing.
 */
class SpacingListener
{
    #[AsCallback('tl_pcu_spacing', 'fields.step.save')]
    public function validateStep(mixed $value): int
    {
        $step = filter_var($value, \FILTER_VALIDATE_INT);

        if (false === $step || $step < UtilityClasses::FIRST_EXTRA_STEP) {
            throw new \RuntimeException(\sprintf($GLOBALS['TL_LANG']['tl_pcu_spacing']['invalidStep'] ?? 'Please enter a whole number from %d.', UtilityClasses::FIRST_EXTRA_STEP));
        }

        return $step;
    }

    /**
     * Shows the CSS the theme has to provide for the step.
     */
    #[AsCallback('tl_pcu_spacing', 'fields.cssSnippet.input_field')]
    public function cssSnippet(DataContainer $dc): string
    {
        $GLOBALS['TL_CSS'][] = \Plakart\CssUtilitiesBundle\Widget\ClassSelectionWidget::STYLESHEET;

        $step = (int) ($dc->getCurrentRecord()['step'] ?? 0);

        return \sprintf(
            '<div class="widget clr pcu-snippet"><h3>%s</h3><pre>%s</pre><p class="tl_help tl_tip">%s</p></div>',
            StringUtil::specialchars($GLOBALS['TL_LANG']['tl_pcu_spacing']['cssSnippet'][0] ?? 'CSS for the theme'),
            StringUtil::specialchars(self::snippet($step >= UtilityClasses::FIRST_EXTRA_STEP ? (string) $step : 'N')),
            StringUtil::specialchars($GLOBALS['TL_LANG']['tl_pcu_spacing']['cssSnippet'][1] ?? ''),
        );
    }

    public static function snippet(string $step): string
    {
        $lines = [
            'html:root {',
            \sprintf('  --pcu-space-%s: 20rem; /* adjust */', $step),
            '}',
            '',
        ];

        foreach (UtilityClasses::PROPERTIES as $prefix => $property) {
            $lines[] = \sprintf('.%s-%s { %s: var(--pcu-space-%s) !important; }', $prefix, $step, $property, $step);
        }

        return implode("\n", $lines);
    }
}
