<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Preset;

use Doctrine\DBAL\Connection;
use Plakart\CssUtilitiesBundle\EventListener\DataContainer\ClassGroupListener;

/**
 * Imports presets: groups are matched by alias, classes by CSS class within their group.
 * Existing records are never changed.
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
            $existingClasses = $existing[$group->alias]['classes'] ?? null;
            $classes = [];

            foreach ($group->classes as $class) {
                $classes[] = ['class' => $class, 'isNew' => null === $existingClasses || !isset($existingClasses[$class->class])];
            }

            $groups[] = ['group' => $group, 'isNew' => null === $existingClasses, 'classes' => $classes];
        }

        return new ImportPlan($groups);
    }

    /**
     * @param array<string, list<string>>|null $selection group alias => CSS classes to import; null = everything new
     *
     * @return array{groups: int, classes: int} number of created records
     */
    public function apply(Preset $preset, ?array $selection = null): array
    {
        return $this->connection->transactional(function () use ($preset, $selection): array {
            $existing = $this->loadExisting();
            $created = ['groups' => 0, 'classes' => 0];

            foreach ($preset->groups as $group) {
                if (null !== $selection && !\array_key_exists($group->alias, $selection)) {
                    continue;
                }

                $classes = array_values(array_filter(
                    $group->classes,
                    static fn (PresetClass $class): bool => null === $selection || \in_array($class->class, $selection[$group->alias], true),
                ));

                if (isset($existing[$group->alias])) {
                    $groupId = $existing[$group->alias]['id'];
                    $classes = array_values(array_filter(
                        $classes,
                        static fn (PresetClass $class): bool => !isset($existing[$group->alias]['classes'][$class->class]),
                    ));
                } else {
                    $groupId = $this->insertGroup($group);
                    ++$created['groups'];
                }

                $created['classes'] += $this->insertClasses($groupId, $classes);
            }

            return $created;
        });
    }

    private function insertGroup(PresetGroup $group): int
    {
        $row = [
            'tstamp' => time(),
            'title' => $group->title,
            'alias' => $group->alias,
            'multiple' => (int) $group->multiple,
            'targets' => serialize(array_keys($group->targets)),
            'published' => (int) $group->published,
        ];

        foreach (ClassGroupListener::TYPE_FIELDS as $key => $field) {
            $types = $group->targets[$key] ?? [];
            $row[$field] = [] === $types ? null : serialize($types);
        }

        $this->connection->insert('tl_pcu_class_group', $row);

        return (int) $this->connection->lastInsertId();
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
     * @return array<string, array{id: int, classes: array<string, true>}>
     */
    private function loadExisting(): array
    {
        $existing = [];
        $aliases = [];

        foreach ($this->connection->fetchAllAssociative('SELECT id, alias FROM tl_pcu_class_group') as $row) {
            $existing[(string) $row['alias']] = ['id' => (int) $row['id'], 'classes' => []];
            $aliases[(int) $row['id']] = (string) $row['alias'];
        }

        foreach ($this->connection->fetchAllAssociative('SELECT pid, cssClass FROM tl_pcu_class') as $row) {
            if (isset($aliases[(int) $row['pid']])) {
                $existing[$aliases[(int) $row['pid']]]['classes'][(string) $row['cssClass']] = true;
            }
        }

        return $existing;
    }
}
