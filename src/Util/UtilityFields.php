<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Util;

final class UtilityFields
{
    public const FIELDS = ['utilityMt', 'utilityMb', 'utilityPt', 'utilityPb'];

    /**
     * Tables that receive the spacing utility fields (see contao/dca).
     */
    public const TABLES = ['tl_content', 'tl_article', 'tl_form_field'];

    /**
     * Registers the four utility select fields (including their sql definition) on the table.
     *
     * Palette manipulation happens later, in UtilityPaletteListener::__invoke(), so that
     * palettes registered by third-party bundles or app DCA files after this file is loaded
     * still receive the utility fields.
     */
    public static function register(string $table): void
    {
        $options = array_map(strval(...), UtilityClasses::steps());

        foreach (self::FIELDS as $field) {
            $GLOBALS['TL_DCA'][$table]['fields'][$field] = [
                'exclude' => true,
                'inputType' => 'select',
                'options' => $options,
                'eval' => ['includeBlankOption' => true, 'tl_class' => 'w25'],
                'sql' => ['type' => 'string', 'length' => 2, 'default' => ''],
            ];
        }
    }
}
