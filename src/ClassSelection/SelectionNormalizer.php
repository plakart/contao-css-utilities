<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\ClassSelection;

use Contao\StringUtil;

/**
 * Reduces a submitted or stored class selection to what the given groups allow.
 */
final class SelectionNormalizer
{
    /**
     * @param mixed            $input  array or serialized array of group alias => class(es)
     * @param list<ClassGroup> $groups groups available for the record
     *
     * @return array<string, list<string>> in group order, without empty groups
     */
    public static function normalize(mixed $input, array $groups): array
    {
        if (\is_string($input)) {
            $input = StringUtil::deserialize($input, true);
        }

        if (!\is_array($input)) {
            return [];
        }

        $selection = [];

        foreach ($groups as $group) {
            $values = $input[$group->alias] ?? [];
            $values = \is_array($values) ? $values : [$values];

            $classes = [];

            foreach ($values as $value) {
                if (\is_string($value) && isset($group->classes[$value]) && !\in_array($value, $classes, true)) {
                    $classes[] = $value;
                }
            }

            if (!$group->multiple) {
                $classes = \array_slice($classes, 0, 1);
            }

            if ([] !== $classes) {
                $selection[$group->alias] = $classes;
            }
        }

        return $selection;
    }

    /**
     * Flattens a normalized selection into a list of classes.
     *
     * @param array<string, list<string>> $selection
     *
     * @return list<string>
     */
    public static function classes(array $selection): array
    {
        return [] === $selection ? [] : array_merge(...array_values($selection));
    }
}
