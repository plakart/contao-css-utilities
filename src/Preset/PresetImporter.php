<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Preset;

use Doctrine\DBAL\Connection;
use Plakart\CssUtilitiesBundle\ClassSelection\ClassGroupRepository;
use Plakart\CssUtilitiesBundle\EventListener\DataContainer\ClassGroupListener;

/**
 * Imports presets: groups are matched by alias, classes by CSS class within their group.
 *
 * New records are created. Existing records are only changed if they are explicitly
 * listed in $updates; alias and CSS class (the matching keys) never change.
 *
 * @phpstan-import-type Change from ImportPlan
 *
 * @phpstan-type ExistingClass array{id: int, label: string, published: bool}
 * @phpstan-type ExistingGroup array{id: int, title: string, multiple: bool, targets: array<string, list<string>>, published: bool, classes: array<string, ExistingClass>}
 */
class PresetImporter
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function plan(Preset $preset): ImportPlan
    {
        $existing = $this->loadExisting();
        $groups = [];

        foreach ($preset->groups as $group) {
            $existingGroup = $existing[$group->alias] ?? null;
            $classes = [];

            foreach ($group->classes as $class) {
                $existingClass = $existingGroup['classes'][$class->class] ?? null;

                $classes[] = [
                    'class' => $class,
                    'isNew' => null === $existingClass,
                    'changes' => null === $existingClass ? [] : self::classChanges($existingClass, $class),
                ];
            }

            $groups[] = [
                'group' => $group,
                'isNew' => null === $existingGroup,
                'changes' => null === $existingGroup ? [] : self::groupChanges($existingGroup, $group),
                'classes' => $classes,
            ];
        }

        return new ImportPlan($groups);
    }

    /**
     * @param array<string, list<string>>|null                                         $selection group alias => new CSS classes to import; null = everything new
     * @param array{groups?: list<string>, classes?: array<string, list<string>>}|null $updates   existing groups (aliases) and classes (alias => CSS classes) to overwrite with the preset values
     *
     * @return array{groups: int, classes: int, updatedGroups: int, updatedClasses: int}
     */
    public function apply(Preset $preset, ?array $selection = null, ?array $updates = null): array
    {
        return $this->connection->transactional(function () use ($preset, $selection, $updates): array {
            $existing = $this->loadExisting();
            $result = ['groups' => 0, 'classes' => 0, 'updatedGroups' => 0, 'updatedClasses' => 0];

            foreach ($preset->groups as $group) {
                $existingGroup = $existing[$group->alias] ?? null;

                if (null !== $existingGroup) {
                    $result['updatedGroups'] += $this->updateGroup($existingGroup, $group, \in_array($group->alias, $updates['groups'] ?? [], true));
                    $result['updatedClasses'] += $this->updateClasses($existingGroup, $group, $updates['classes'][$group->alias] ?? []);
                }

                if (null !== $selection && !\array_key_exists($group->alias, $selection)) {
                    continue;
                }

                $classes = array_values(array_filter(
                    $group->classes,
                    static fn (PresetClass $class): bool => (null === $selection || \in_array($class->class, $selection[$group->alias], true))
                        && !isset($existingGroup['classes'][$class->class]),
                ));

                if (null === $existingGroup) {
                    $groupId = $this->insertGroup($group);
                    ++$result['groups'];
                } else {
                    $groupId = $existingGroup['id'];
                }

                $result['classes'] += $this->insertClasses($groupId, $classes);
            }

            return $result;
        });
    }

    /**
     * @param ExistingGroup $existing
     *
     * @return list<Change>
     */
    private static function groupChanges(array $existing, PresetGroup $group): array
    {
        $changes = [];

        if ($existing['title'] !== $group->title) {
            $changes[] = ['field' => 'title', 'old' => $existing['title'], 'new' => $group->title];
        }

        if ($existing['multiple'] !== $group->multiple) {
            $changes[] = ['field' => 'multiple', 'old' => self::bool($existing['multiple']), 'new' => self::bool($group->multiple)];
        }

        $old = self::targetsToString($existing['targets']);
        $new = self::targetsToString($group->targets);

        if ($old !== $new) {
            $changes[] = ['field' => 'targets', 'old' => $old, 'new' => $new];
        }

        if ($existing['published'] !== $group->published) {
            $changes[] = ['field' => 'published', 'old' => self::bool($existing['published']), 'new' => self::bool($group->published)];
        }

        return $changes;
    }

    /**
     * @param ExistingClass $existing
     *
     * @return list<Change>
     */
    private static function classChanges(array $existing, PresetClass $class): array
    {
        $changes = [];

        if ($existing['label'] !== $class->label) {
            $changes[] = ['field' => 'label', 'old' => $existing['label'], 'new' => $class->label];
        }

        if ($existing['published'] !== $class->published) {
            $changes[] = ['field' => 'published', 'old' => self::bool($existing['published']), 'new' => self::bool($class->published)];
        }

        return $changes;
    }

    /**
     * Readable, order-independent form of the targets, e.g. "article; content: hyperlink, text".
     *
     * @param array<string, list<string>> $targets
     */
    private static function targetsToString(array $targets): string
    {
        ksort($targets);
        $parts = [];

        foreach ($targets as $key => $types) {
            sort($types);
            $parts[] = [] === $types ? $key : $key.': '.implode(', ', $types);
        }

        return implode('; ', $parts);
    }

    private static function bool(bool $value): string
    {
        return $value ? 'yes' : 'no';
    }

    /**
     * @param ExistingGroup $existing
     */
    private function updateGroup(array $existing, PresetGroup $group, bool $requested): int
    {
        if (!$requested || [] === self::groupChanges($existing, $group)) {
            return 0;
        }

        $this->connection->update('tl_pcu_class_group', [
            'tstamp' => time(),
            'title' => $group->title,
            'multiple' => (int) $group->multiple,
            'published' => (int) $group->published,
            ...self::targetColumns($group),
        ], ['id' => $existing['id']]);

        return 1;
    }

    /**
     * @param ExistingGroup $existing
     * @param list<string>  $requested CSS classes to update
     */
    private function updateClasses(array $existing, PresetGroup $group, array $requested): int
    {
        $count = 0;

        foreach ($group->classes as $class) {
            $existingClass = $existing['classes'][$class->class] ?? null;

            if (null === $existingClass || !\in_array($class->class, $requested, true) || [] === self::classChanges($existingClass, $class)) {
                continue;
            }

            $this->connection->update('tl_pcu_class', [
                'tstamp' => time(),
                'label' => $class->label,
                'published' => (int) $class->published,
            ], ['id' => $existingClass['id']]);

            ++$count;
        }

        return $count;
    }

    private function insertGroup(PresetGroup $group): int
    {
        $this->connection->insert('tl_pcu_class_group', [
            'tstamp' => time(),
            'title' => $group->title,
            'alias' => $group->alias,
            'multiple' => (int) $group->multiple,
            'published' => (int) $group->published,
            ...self::targetColumns($group),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    /**
     * @return array<string, string|null>
     */
    private static function targetColumns(PresetGroup $group): array
    {
        $columns = ['targets' => serialize(array_keys($group->targets))];

        foreach (ClassGroupListener::TYPE_FIELDS as $key => $field) {
            $types = $group->targets[$key] ?? [];
            $columns[$field] = [] === $types ? null : serialize($types);
        }

        return $columns;
    }

    /**
     * @param list<PresetClass> $classes
     */
    private function insertClasses(int $groupId, array $classes): int
    {
        $sorting = (int) $this->connection->fetchOne('SELECT MAX(sorting) FROM tl_pcu_class WHERE pid = ?', [$groupId]);

        foreach ($classes as $class) {
            $sorting += 128;

            $this->connection->insert('tl_pcu_class', [
                'pid' => $groupId,
                'tstamp' => time(),
                'sorting' => $sorting,
                'label' => $class->label,
                'cssClass' => $class->class,
                'published' => (int) $class->published,
            ]);
        }

        return \count($classes);
    }

    /**
     * @return array<string, ExistingGroup>
     */
    private function loadExisting(): array
    {
        $existing = [];
        $aliases = [];

        foreach ($this->connection->fetchAllAssociative('SELECT * FROM tl_pcu_class_group') as $row) {
            $existing[(string) $row['alias']] = [
                'id' => (int) $row['id'],
                'title' => (string) $row['title'],
                'multiple' => (bool) $row['multiple'],
                'targets' => ClassGroupRepository::targetsFromRow($row),
                'published' => (bool) $row['published'],
                'classes' => [],
            ];

            $aliases[(int) $row['id']] = (string) $row['alias'];
        }

        foreach ($this->connection->fetchAllAssociative('SELECT id, pid, cssClass, label, published FROM tl_pcu_class') as $row) {
            if (isset($aliases[(int) $row['pid']])) {
                $existing[$aliases[(int) $row['pid']]]['classes'][(string) $row['cssClass']] = [
                    'id' => (int) $row['id'],
                    'label' => (string) $row['label'],
                    'published' => (bool) $row['published'],
                ];
            }
        }

        return $existing;
    }
}
