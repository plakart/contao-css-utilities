<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Preset;

/**
 * Shows which groups and classes of a preset are new and which already exist.
 */
final readonly class ImportPlan
{
    /**
     * @param list<array{group: PresetGroup, isNew: bool, classes: list<array{class: PresetClass, isNew: bool}>}> $groups
     */
    public function __construct(public array $groups)
    {
    }

    public function countNewGroups(): int
    {
        return \count(array_filter($this->groups, static fn (array $group): bool => $group['isNew']));
    }

    public function countNewClasses(): int
    {
        $count = 0;

        foreach ($this->groups as $group) {
            $count += \count(array_filter($group['classes'], static fn (array $class): bool => $class['isNew']));
        }

        return $count;
    }

    public function hasChanges(): bool
    {
        return $this->countNewGroups() > 0 || $this->countNewClasses() > 0;
    }
}
