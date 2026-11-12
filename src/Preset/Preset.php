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
     * @param list<PresetGroup>       $groups
     * @param list<PresetSpacingStep> $spacing additional spacing steps (13+)
     */
    public function __construct(
        public array $groups = [],
        public array $spacing = [],
    ) {
    }

    public function isEmpty(): bool
    {
        return [] === $this->groups && [] === $this->spacing;
    }

    public function countClasses(): int
    {
        return array_sum(array_map(static fn (PresetGroup $group): int => \count($group->classes), $this->groups));
    }
}
