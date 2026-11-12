<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\EventListener\DataContainer;

use Contao\DataContainer;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Plakart\CssUtilitiesBundle\ClassSelection\ClassGroupRepository;
use Plakart\CssUtilitiesBundle\ClassSelection\SelectionNormalizer;
use Plakart\CssUtilitiesBundle\EventListener\LoadDataContainerListener;
use Plakart\CssUtilitiesBundle\Target\Target;
use Plakart\CssUtilitiesBundle\Target\TargetRegistry;
use Plakart\CssUtilitiesBundle\Util\ClassAttributeMerger;
use Plakart\CssUtilitiesBundle\Util\UtilityClassMerger;
use Plakart\CssUtilitiesBundle\Util\UtilityFields;

/**
 * config.onsubmit of every target table: writes the spacing utilities and the selected
 * classes into the record's class column, keeping all other classes.
 */
class ClassAttributeListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly TargetRegistry $targets,
        private readonly ClassGroupRepository $groups,
    ) {
    }

    public function __invoke(DataContainer $dc): void
    {
        $target = $this->targets->forTable((string) $dc->table);

        if (null === $target || !$dc->id) {
            return;
        }

        $row = $this->connection->fetchAssociative(
            \sprintf('SELECT * FROM %s WHERE id = ?', $target->table),
            [(int) $dc->id],
        );

        if (false === $row) {
            return;
        }

        $updates = $this->computeUpdates($target, $row);

        if ([] !== $updates) {
            $this->connection->update($target->table, $updates, ['id' => (int) $dc->id]);
        }
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, string|null> changed columns only
     */
    public function computeUpdates(Target $target, array $row): array
    {
        $type = null === $target->typeField ? null : (string) ($row[$target->typeField] ?? '');
        $groups = $this->groups->findPublishedFor($target->key, $type);
        $selection = SelectionNormalizer::normalize($row[LoadDataContainerListener::FIELD] ?? null, $groups);

        $strip = $this->groups->findAllClassTokens();
        $add = [];
        $hasUtilities = \in_array($target->table, UtilityFields::TABLES, true);

        if ($hasUtilities) {
            $add = UtilityClassMerger::classes([
                'mt' => (string) ($row['utilityMt'] ?? ''),
                'mb' => (string) ($row['utilityMb'] ?? ''),
                'pt' => (string) ($row['utilityPt'] ?? ''),
                'pb' => (string) ($row['utilityPb'] ?? ''),
            ]);
        }

        $add = [...$add, ...SelectionNormalizer::classes($selection)];
        $updates = [];

        if ($target->isCssId()) {
            $cssId = StringUtil::deserialize($row[$target->column] ?? null, true) + ['', ''];
            $old = [(string) $cssId[0], (string) $cssId[1]];
            $new = [$old[0], ClassAttributeMerger::merge($old[1], $hasUtilities ? [...$strip, ...UtilityClassMerger::stripList($old[1])] : $strip, $add)];

            if ($new !== $old) {
                $updates[$target->column] = serialize($new);
            }
        } else {
            $old = (string) ($row[$target->column] ?? '');
            $new = ClassAttributeMerger::merge($old, $hasUtilities ? [...$strip, ...UtilityClassMerger::stripList($old)] : $strip, $add);

            if ($new !== $old) {
                $updates[$target->column] = $new;
            }
        }

        // Drop selections of groups that no longer apply (e.g. after a type change)
        $stored = $row[LoadDataContainerListener::FIELD] ?? null;
        $normalized = [] === $selection ? null : serialize($selection);

        if ($normalized !== $stored && !(null === $normalized && ('' === $stored || null === $stored))) {
            $updates[LoadDataContainerListener::FIELD] = $normalized;
        }

        return $updates;
    }
}
