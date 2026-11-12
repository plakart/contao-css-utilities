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
        return ClassAttributeMerger::merge($existing, UtilityClasses::all(), self::classes($selection));
    }

    /**
     * Turns a spacing selection into class names.
     *
     * @param array<string, string> $selection prefix => step ('' = no class)
     *
     * @return list<string>
     */
    public static function classes(array $selection): array
    {
        $classes = [];

        foreach ($selection as $prefix => $step) {
            if ('' !== trim($step)) {
                $classes[] = $prefix.'-'.trim($step);
            }
        }

        return $classes;
    }
}
