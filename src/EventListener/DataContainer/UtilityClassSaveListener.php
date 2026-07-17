<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Plakart\CssUtilitiesBundle\Util\UtilityClassMerger;

class UtilityClassSaveListener
{
    public function __construct(private readonly Connection $connection)
    {
    }

    #[AsCallback('tl_content', 'config.onsubmit')]
    #[AsCallback('tl_article', 'config.onsubmit')]
    public function updateCssId(DataContainer $dc): void
    {
        $row = $this->fetchRow($dc);

        if (null === $row) {
            return;
        }

        $cssId = StringUtil::deserialize($row['cssID'] ?? null, true) + ['', ''];
        $cssId[1] = UtilityClassMerger::merge((string) $cssId[1], $this->getSelection($row));

        $this->connection->update(
            $dc->table,
            ['cssID' => serialize([(string) $cssId[0], $cssId[1]])],
            ['id' => (int) $dc->id],
        );
    }

    #[AsCallback('tl_form_field', 'config.onsubmit')]
    public function updateClass(DataContainer $dc): void
    {
        $row = $this->fetchRow($dc);

        if (null === $row) {
            return;
        }

        $class = UtilityClassMerger::merge((string) ($row['class'] ?? ''), $this->getSelection($row));

        $this->connection->update('tl_form_field', ['class' => $class], ['id' => (int) $dc->id]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchRow(DataContainer $dc): ?array
    {
        $row = $this->connection->fetchAssociative(
            sprintf('SELECT * FROM %s WHERE id = ?', $dc->table),
            [(int) $dc->id],
        );

        return false === $row ? null : $row;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, string>
     */
    private function getSelection(array $row): array
    {
        return [
            'mt' => (string) ($row['utilityMt'] ?? ''),
            'mb' => (string) ($row['utilityMb'] ?? ''),
            'pt' => (string) ($row['utilityPt'] ?? ''),
            'pb' => (string) ($row['utilityPb'] ?? ''),
        ];
    }
}
