<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Util;

final class UtilityClassMerger
{
    /**
     * Strips all known utility classes from $existing and appends the current selection.
     *
     * @param array<string, string> $selection prefix => step ('' = no class)
     */
    public static function merge(string $existing, array $selection): string
    {
        $classes = preg_split('/\s+/', trim($existing), -1, \PREG_SPLIT_NO_EMPTY) ?: [];
        $classes = array_values(array_diff($classes, UtilityClasses::all()));

        foreach ($selection as $prefix => $step) {
            if ('' === trim($step)) {
                continue;
            }

            $classes[] = $prefix.'-'.$step;
        }

        return implode(' ', $classes);
    }
}
