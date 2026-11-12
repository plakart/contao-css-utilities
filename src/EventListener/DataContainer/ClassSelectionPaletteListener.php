<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\EventListener\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Plakart\CssUtilitiesBundle\ClassSelection\ClassGroupRepository;
use Plakart\CssUtilitiesBundle\EventListener\LoadDataContainerListener;
use Plakart\CssUtilitiesBundle\Target\Target;
use Plakart\CssUtilitiesBundle\Target\TargetRegistry;
use Plakart\CssUtilitiesBundle\Util\UtilityFields;

/**
 * config.onload of every target table: adds the spacing fields (where supported) and
 * the class selection field, if at least one class group applies to the record.
 *
 * Both run in one callback so that the legend order does not depend on callback priorities.
 */
class ClassSelectionPaletteListener
{
    public const LEGEND = UtilityPaletteListener::LEGEND;

    public function __construct(
        private readonly TargetRegistry $targets,
        private readonly ClassGroupRepository $groups,
        private readonly Connection $connection,
        private readonly UtilityPaletteListener $utilityPalette,
    ) {
    }

    public function __invoke(?DataContainer $dc = null): void
    {
        $table = (string) $dc?->table;
        $target = $this->targets->forTable($table);

        if (null === $target) {
            return;
        }

        $hasUtilities = \in_array($table, UtilityFields::TABLES, true);

        if ($hasUtilities) {
            ($this->utilityPalette)($dc);
        }

        if (!$this->hasGroups($target, $dc)) {
            return;
        }

        // Tables with spacing fields already have the legend; the class selection follows them
        $manipulator = PaletteManipulator::create()
            ->addLegend(self::LEGEND, 'expert_legend', PaletteManipulator::POSITION_BEFORE, true)
            ->addField(LoadDataContainerListener::FIELD, self::LEGEND, PaletteManipulator::POSITION_APPEND)
        ;

        foreach ($GLOBALS['TL_DCA'][$table]['palettes'] ?? [] as $name => $palette) {
            if (\is_string($palette) && '__selector__' !== $name) {
                $manipulator->applyToPalette($name, $table);
            }
        }
    }

    /**
     * With a record ID only the groups for its type count; without one (e.g. "edit
     * multiple") any group of the target is enough, the widget filters per record.
     */
    private function hasGroups(Target $target, DataContainer $dc): bool
    {
        if (null === $target->typeField || !$dc->id) {
            return $this->groups->hasPublishedForTarget($target->key);
        }

        $type = $this->connection->fetchOne(
            \sprintf('SELECT %s FROM %s WHERE id = ?', $target->typeField, $target->table),
            [(int) $dc->id],
        );

        return [] !== $this->groups->findPublishedFor($target->key, false === $type ? null : (string) $type);
    }
}
