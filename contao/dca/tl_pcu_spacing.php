<?php

declare(strict_types=1);

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_pcu_spacing'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'step' => 'unique',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['step'],
            'flag' => DataContainer::SORT_ASC,
            'panelLayout' => 'limit',
        ],
        'label' => [
            'fields' => ['step', 'label'],
            'format' => '%s <span class="tl_gray">%s</span>',
        ],
        'global_operations' => [
            'pcuGroups' => [
                'href' => 'table=tl_pcu_class_group',
                'icon' => 'back.svg',
                'primary' => true,
            ],
            'all',
        ],
    ],
    'palettes' => [
        'default' => '{spacing_legend},step,label,cssSnippet',
    ],
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true],
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'step' => [
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'rgxp' => 'natural', 'unique' => true, 'doNotCopy' => true, 'maxlength' => 6, 'tl_class' => 'w50'],
            // NULL instead of 0, so a new record shows an empty field
            'sql' => ['type' => 'integer', 'unsigned' => true, 'notnull' => false],
        ],
        'label' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'cssSnippet' => [
            'exclude' => false,
        ],
    ],
];
