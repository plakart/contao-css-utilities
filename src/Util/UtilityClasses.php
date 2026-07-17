<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Util;

final class UtilityClasses
{
    public const PROPERTIES = [
        'mt' => 'margin-top',
        'mb' => 'margin-bottom',
        'pt' => 'padding-top',
        'pb' => 'padding-bottom',
    ];

    public const SCALE = [
        1 => 'clamp(0.25rem, 0.2rem + 0.25vw, 0.5rem)',
        2 => 'clamp(0.5rem, 0.4rem + 0.5vw, 1rem)',
        3 => 'clamp(0.75rem, 0.6rem + 0.75vw, 1.5rem)',
        4 => 'clamp(1rem, 0.8rem + 1vw, 2rem)',
        5 => 'clamp(1.5rem, 1.2rem + 1.5vw, 3rem)',
        6 => 'clamp(2rem, 1.6rem + 2vw, 4rem)',
        7 => 'clamp(3rem, 2.4rem + 3vw, 6rem)',
        8 => 'clamp(4rem, 3.2rem + 4vw, 8rem)',
    ];

    /**
     * @return list<int>
     */
    public static function steps(): array
    {
        return range(0, 8);
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $classes = [];

        foreach (array_keys(self::PROPERTIES) as $prefix) {
            foreach (self::steps() as $step) {
                $classes[] = $prefix.'-'.$step;
            }
        }

        return $classes;
    }

    public static function generateCss(): string
    {
        $lines = [':root {'];

        foreach (self::SCALE as $step => $value) {
            $lines[] = sprintf('  --space-%d: %s;', $step, $value);
        }

        $lines[] = '}';
        $lines[] = '';

        foreach (self::PROPERTIES as $prefix => $property) {
            foreach (self::steps() as $step) {
                $value = 0 === $step ? '0' : sprintf('var(--space-%d)', $step);
                $lines[] = sprintf('.%s-%d { %s: %s !important; }', $prefix, $step, $property, $value);
            }
        }

        return implode("\n", $lines)."\n";
    }
}
