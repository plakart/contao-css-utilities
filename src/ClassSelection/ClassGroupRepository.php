<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\ClassSelection;

use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Plakart\CssUtilitiesBundle\EventListener\DataContainer\ClassGroupListener;
use Plakart\CssUtilitiesBundle\Util\ClassAttributeMerger;
use Symfony\Contracts\Service\ResetInterface;

class ClassGroupRepository implements ResetInterface
{
    /**
     * @var list<ClassGroup>|null
     */
    private ?array $groups = null;

    /**
     * @var list<string>|null
     */
    private ?array $tokens = null;

    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * Published groups with at least one published class that apply to the target and type.
     *
     * @return list<ClassGroup>
     */
    public function findPublishedFor(string $targetKey, ?string $type): array
    {
        return array_values(array_filter(
            $this->loadGroups(),
            static fn (ClassGroup $group): bool => $group->appliesTo($targetKey, $type),
        ));
    }

    /**
     * Whether any published group (with published classes) targets the key, regardless of types.
     */
    public function hasPublishedForTarget(string $targetKey): bool
    {
        foreach ($this->loadGroups() as $group) {
            if (isset($group->targets[$targetKey])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every class token defined in any group, published or not.
     *
     * @return list<string>
     */
    public function findAllClassTokens(): array
    {
        if (null === $this->tokens) {
            $classes = $this->connection->fetchFirstColumn('SELECT cssClass FROM tl_pcu_class');
            $this->tokens = array_values(array_unique(ClassAttributeMerger::tokenize(implode(' ', $classes))));
        }

        return $this->tokens;
    }

    public function reset(): void
    {
        $this->groups = null;
        $this->tokens = null;
    }

    /**
     * @return list<ClassGroup>
     */
    private function loadGroups(): array
    {
        if (null !== $this->groups) {
            return $this->groups;
        }

        $classes = [];

        $rows = $this->connection->fetchAllAssociative(
            'SELECT pid, cssClass, label FROM tl_pcu_class WHERE published = ? ORDER BY pid, sorting, id',
            [1],
        );

        foreach ($rows as $row) {
            $classes[(int) $row['pid']][(string) $row['cssClass']] = (string) $row['label'];
        }

        $groups = [];

        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM tl_pcu_class_group WHERE published = ? ORDER BY title, id',
            [1],
        );

        foreach ($rows as $row) {
            $id = (int) $row['id'];

            if (empty($classes[$id])) {
                continue;
            }

            $groups[] = new ClassGroup(
                $id,
                (string) $row['alias'],
                (string) $row['title'],
                (bool) $row['multiple'],
                self::targetsFromRow($row),
                $classes[$id],
            );
        }

        return $this->groups = $groups;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, list<string>>
     */
    public static function targetsFromRow(array $row): array
    {
        $targets = [];

        foreach (StringUtil::deserialize($row['targets'] ?? null, true) as $key) {
            $field = ClassGroupListener::TYPE_FIELDS[$key] ?? null;
            $types = null === $field ? [] : StringUtil::deserialize($row[$field] ?? null, true);

            $targets[(string) $key] = array_values(array_map(strval(...), $types));
        }

        return $targets;
    }
}
