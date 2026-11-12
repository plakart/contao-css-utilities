<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Preset;

/**
 * A set of class groups in the exchange format shared by export, import and the theme preset file.
 */
final readonly class Preset
{
    public const FORMAT = 'plakart-css-classes';

    public const VERSION = 1;

    /**
     * @param list<PresetGroup> $groups
     */
    public function __construct(public array $groups = [])
    {
    }

    public function countClasses(): int
    {
        return array_sum(array_map(static fn (PresetGroup $group): int => \count($group->classes), $this->groups));
    }
}
