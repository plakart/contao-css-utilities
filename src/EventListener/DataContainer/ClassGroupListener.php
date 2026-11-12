<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\EventListener\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Plakart\CssUtilitiesBundle\Target\TargetRegistry;

/**
 * DCA callbacks of tl_pcu_class_group: target and type options, type fields, alias.
 */
class ClassGroupListener
{
    /**
     * Type restriction field per target key.
     */
    public const TYPE_FIELDS = [
        'content' => 'contentTypes',
        'module' => 'moduleTypes',
        'form_field' => 'formFieldTypes',
    ];

    public function __construct(
        private readonly TargetRegistry $targets,
        private readonly Connection $connection,
        private readonly Slug $slug,
    ) {
    }

    /**
     * @return array<string, string>
     */
    #[AsCallback('tl_pcu_class_group', 'fields.targets.options')]
    public function targetOptions(): array
    {
        $options = [];

        foreach (array_keys($this->targets->all()) as $key) {
            $options[$key] = $GLOBALS['TL_LANG']['tl_pcu_class_group']['targetOptions'][$key] ?? $key;
        }

        return $options;
    }

    /**
     * @return array<string, array<string, string>>
     */
    #[AsCallback('tl_pcu_class_group', 'fields.contentTypes.options')]
    public function contentTypeOptions(): array
    {
        return self::groupedTypeOptions($GLOBALS['TL_CTE'] ?? [], $GLOBALS['TL_LANG']['CTE'] ?? []);
    }

    /**
     * @return array<string, array<string, string>>
     */
    #[AsCallback('tl_pcu_class_group', 'fields.moduleTypes.options')]
    public function moduleTypeOptions(): array
    {
        return self::groupedTypeOptions($GLOBALS['FE_MOD'] ?? [], $GLOBALS['TL_LANG']['FMD'] ?? []);
    }

    /**
     * @return array<string, string>
     */
    #[AsCallback('tl_pcu_class_group', 'fields.formFieldTypes.options')]
    public function formFieldTypeOptions(): array
    {
        $options = [];

        foreach (array_keys($GLOBALS['TL_FFL'] ?? []) as $type) {
            $options[$type] = self::label($GLOBALS['TL_LANG']['FFL'] ?? [], $type);
        }

        return $options;
    }

    /**
     * Shows the type restriction fields of the checked targets.
     */
    #[AsCallback('tl_pcu_class_group', 'config.onload')]
    public function addTypeFields(?DataContainer $dc = null): void
    {
        if (null === $dc || !$dc->id) {
            return;
        }

        $targets = $this->connection->fetchOne('SELECT targets FROM tl_pcu_class_group WHERE id = ?', [(int) $dc->id]);
        $targets = StringUtil::deserialize(\is_string($targets) ? $targets : null, true);

        $fields = [];

        foreach (self::TYPE_FIELDS as $key => $field) {
            if (\in_array($key, $targets, true) && $this->targets->has($key)) {
                $fields[] = $field;
            }
        }

        if ([] === $fields) {
            return;
        }

        PaletteManipulator::create()
            ->addField($fields, 'targets', PaletteManipulator::POSITION_AFTER)
            ->applyToPalette('default', 'tl_pcu_class_group')
        ;
    }

    /**
     * Generates the alias from the title if empty and validates it.
     */
    #[AsCallback('tl_pcu_class_group', 'fields.alias.save')]
    public function saveAlias(mixed $value, DataContainer $dc): string
    {
        $value = trim((string) $value);

        if ('' !== $value) {
            if (!self::isValidAlias($value)) {
                throw new \RuntimeException($GLOBALS['TL_LANG']['tl_pcu_class_group']['invalidAlias'] ?? 'Invalid alias.');
            }

            return $value;
        }

        $title = (string) ($dc->getCurrentRecord()['title'] ?? '');

        return $this->slug->generate(
            '' !== $title ? $title : 'group',
            ['validChars' => '0-9a-z', 'delimiter' => '-', 'locale' => 'de'],
            fn (string $alias): bool => false !== $this->connection->fetchOne(
                'SELECT id FROM tl_pcu_class_group WHERE alias = ? AND id != ?',
                [$alias, (int) $dc->id],
            ),
            'group-',
        );
    }

    public static function isValidAlias(string $alias): bool
    {
        return 1 === preg_match('/^[a-z0-9_-]+$/', $alias);
    }

    /**
     * Builds select options grouped like the core type selects.
     *
     * @param array<string, mixed> $registry e.g. $GLOBALS['TL_CTE'] (group => [type => class])
     * @param array<string, mixed> $labels   e.g. $GLOBALS['TL_LANG']['CTE']
     *
     * @return array<string, array<string, string>>
     */
    public static function groupedTypeOptions(array $registry, array $labels): array
    {
        $options = [];

        foreach ($registry as $group => $types) {
            if (!\is_array($types) || [] === $types) {
                continue;
            }

            $groupLabel = self::label($labels, (string) $group);

            foreach (array_keys($types) as $type) {
                $options[$groupLabel][(string) $type] = self::label($labels, (string) $type);
            }
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $labels
     */
    private static function label(array $labels, string $key): string
    {
        $label = $labels[$key] ?? null;

        if (\is_array($label)) {
            $label = $label[0] ?? null;
        }

        return \is_string($label) && '' !== $label ? $label : $key;
    }
}
