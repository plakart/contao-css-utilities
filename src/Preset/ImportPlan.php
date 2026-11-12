<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Preset;

/**
 * Shows which groups and classes of a preset are new, which already exist and how
 * existing ones differ from the preset.
 *
 * @phpstan-type Change array{field: string, old: string, new: string}
 * @phpstan-type ClassItem array{class: PresetClass, isNew: bool, changes: list<Change>}
 * @phpstan-type GroupItem array{group: PresetGroup, isNew: bool, changes: list<Change>, classes: list<ClassItem>}
 */
final readonly class ImportPlan
{
    /**
     * @param list<GroupItem> $groups
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
        return $this->countClasses(static fn (array $class): bool => $class['isNew']);
    }

    public function countChangedGroups(): int
    {
        return \count(array_filter($this->groups, static fn (array $group): bool => [] !== $group['changes']));
    }

    public function countChangedClasses(): int
    {
        return $this->countClasses(static fn (array $class): bool => [] !== $class['changes']);
    }

    public function hasChanges(): bool
    {
        return $this->countNewGroups() + $this->countNewClasses() + $this->countChangedGroups() + $this->countChangedClasses() > 0;
    }

    /**
     * @param \Closure(ClassItem): bool $filter
     */
    private function countClasses(\Closure $filter): int
    {
        $count = 0;

        foreach ($this->groups as $group) {
            $count += \count(array_filter($group['classes'], $filter));
        }

        return $count;
    }
}
