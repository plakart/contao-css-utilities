<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Preset;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Plakart\CssUtilitiesBundle\ClassSelection\ClassGroupRepository;

/**
 * Builds a preset from the class groups in the database.
 */
class PresetExporter
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * $groupIds limits the groups (null = all), $classIds limits the classes of
     * those groups (null = all classes of the exported groups).
     *
     * @param list<int>|null $groupIds
     * @param list<int>|null $classIds
     */
    public function export(?array $groupIds = null, ?array $classIds = null): Preset
    {
        $classRows = $this->connection->fetchAllAssociative('SELECT * FROM tl_pcu_class ORDER BY pid, sorting, id');

        if (null !== $classIds) {
            $classRows = array_values(array_filter($classRows, static fn (array $row): bool => \in_array((int) $row['id'], $classIds, true)));
        }

        $classesByGroup = [];

        foreach ($classRows as $row) {
            $classesByGroup[(int) $row['pid']][] = new PresetClass((string) $row['cssClass'], (string) $row['label'], (bool) $row['published']);
        }

        if (null === $groupIds) {
            $groupRows = $this->connection->fetchAllAssociative('SELECT * FROM tl_pcu_class_group ORDER BY title, id');
        } else {
            $groupRows = [] === $groupIds ? [] : $this->connection->fetchAllAssociative(
                'SELECT * FROM tl_pcu_class_group WHERE id IN (?) ORDER BY title, id',
                [$groupIds],
                [ArrayParameterType::INTEGER],
            );
        }

        $groups = [];

        foreach ($groupRows as $row) {
            $groups[] = new PresetGroup(
                (string) $row['alias'],
                (string) $row['title'],
                (bool) $row['multiple'],
                ClassGroupRepository::targetsFromRow($row),
                $classesByGroup[(int) $row['id']] ?? [],
                (bool) $row['published'],
            );
        }

        return new Preset($groups);
    }
}
