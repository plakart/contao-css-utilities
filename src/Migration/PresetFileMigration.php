<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Plakart\CssUtilitiesBundle\Preset\InvalidPresetException;
use Plakart\CssUtilitiesBundle\Preset\Preset;
use Plakart\CssUtilitiesBundle\Preset\PresetImporter;
use Plakart\CssUtilitiesBundle\Preset\PresetSerializer;

/**
 * Imports the theme preset file (default files/theme/css-classes.json) once, as long as
 * neither class groups nor additional spacing steps exist yet.
 *
 * Contao runs migrations until none is pending, so shouldRun() must become false after
 * run(): an invalid or empty file therefore never runs; the back-end import shows the error.
 */
class PresetFileMigration extends AbstractMigration
{
    public function __construct(
        private readonly Connection $connection,
        private readonly PresetImporter $importer,
        private readonly string $presetFile,
    ) {
    }

    public function getName(): string
    {
        return 'CSS utilities: import CSS class preset file';
    }

    public function shouldRun(): bool
    {
        if (!is_file($this->presetFile) || !$this->connection->createSchemaManager()->tablesExist(['tl_pcu_class_group', 'tl_pcu_class', 'tl_pcu_spacing'])) {
            return false;
        }

        if ((int) $this->connection->fetchOne('SELECT (SELECT COUNT(*) FROM tl_pcu_class_group) + (SELECT COUNT(*) FROM tl_pcu_spacing)') > 0) {
            return false;
        }

        return !($this->loadPreset()?->isEmpty() ?? true);
    }

    public function run(): MigrationResult
    {
        $preset = $this->loadPreset();

        if (null === $preset) {
            return $this->createResult(false, \sprintf('Could not read %s.', $this->presetFile));
        }

        $created = $this->importer->apply($preset);

        return $this->createResult(true, \sprintf(
            'Imported %d CSS class groups, %d classes and %d additional spacing steps from %s.',
            $created['groups'],
            $created['classes'],
            $created['spacing'],
            $this->presetFile,
        ));
    }

    private function loadPreset(): ?Preset
    {
        $json = @file_get_contents($this->presetFile);

        if (false === $json) {
            return null;
        }

        try {
            return PresetSerializer::fromJson($json);
        } catch (InvalidPresetException) {
            return null;
        }
    }
}
