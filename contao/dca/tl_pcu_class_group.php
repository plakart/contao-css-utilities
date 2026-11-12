<?php

declare(strict_types=1);

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_pcu_class_group'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_pcu_class'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'markAsCopy' => 'title',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'alias' => 'unique',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['title'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'search,limit',
            'defaultSearchField' => 'title',
        ],
        'label' => [
            'fields' => ['title', 'alias'],
            'format' => '%s <span class="tl_gray">[%s]</span>',
        ],
        'global_operations' => [
            'all',
            '-',
            'pcuSpacing' => [
                'href' => 'table=tl_pcu_spacing',
                'icon' => 'bundles/plakartcssutilities/icons/spacing.svg',
                'primary' => true,
            ],
            'pcuImport' => [
                'route' => Plakart\CssUtilitiesBundle\Controller\Backend\ImportController::ROUTE,
                'icon' => 'theme_import.svg',
            ],
            'pcuExport' => [
                'route' => Plakart\CssUtilitiesBundle\Controller\Backend\ExportController::ROUTE,
                'icon' => 'theme_export.svg',
            ],
        ],
    ],
    'palettes' => [
        'default' => '{title_legend},title,alias,multiple;{target_legend},targets;{publish_legend},published',
    ],
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true],
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'title' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'alias' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['doNotCopy' => true, 'unique' => true, 'maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 64, 'default' => '', 'platformOptions' => ['collation' => 'ascii_bin']],
        ],
        'multiple' => [
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'targets' => [
            'inputType' => 'checkbox',
            'eval' => ['multiple' => true, 'mandatory' => true, 'submitOnChange' => true, 'tl_class' => 'clr'],
            'sql' => ['type' => 'blob', 'length' => 65535, 'notnull' => false],
        ],
        'contentTypes' => [
            'inputType' => 'select',
            'eval' => ['multiple' => true, 'chosen' => true, 'tl_class' => 'clr'],
            'sql' => ['type' => 'blob', 'length' => 65535, 'notnull' => false],
        ],
        'moduleTypes' => [
            'inputType' => 'select',
            'eval' => ['multiple' => true, 'chosen' => true, 'tl_class' => 'clr'],
            'sql' => ['type' => 'blob', 'length' => 65535, 'notnull' => false],
        ],
        'formFieldTypes' => [
            'inputType' => 'select',
            'eval' => ['multiple' => true, 'chosen' => true, 'tl_class' => 'clr'],
            'sql' => ['type' => 'blob', 'length' => 65535, 'notnull' => false],
        ],
        'published' => [
            'toggle' => true,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true],
            'sql' => ['type' => 'boolean', 'default' => true],
        ],
    ],
];
