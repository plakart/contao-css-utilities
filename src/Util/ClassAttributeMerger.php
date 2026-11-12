<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Util;

final class ClassAttributeMerger
{
    /**
     * Removes every known class from a class attribute and appends the current selection.
     *
     * Entries of $strip and $add may contain several space-separated classes. Foreign
     * classes keep their order; the result contains every class only once.
     *
     * @param list<string> $strip classes managed by the bundle
     * @param list<string> $add   classes to append
     */
    public static function merge(string $current, array $strip, array $add): string
    {
        $stripTokens = array_flip(self::tokenize(implode(' ', $strip)));

        $classes = array_filter(
            self::tokenize($current),
            static fn (string $class): bool => !isset($stripTokens[$class]),
        );

        $classes = [...$classes, ...self::tokenize(implode(' ', $add))];

        return implode(' ', array_unique($classes));
    }

    /**
     * @return list<string>
     */
    public static function tokenize(string $classes): array
    {
        return preg_split('/\s+/', trim($classes), -1, \PREG_SPLIT_NO_EMPTY) ?: [];
    }
}
