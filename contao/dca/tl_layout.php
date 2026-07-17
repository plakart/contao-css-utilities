<?php

declare(strict_types=1);

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_layout']['fields']['disableUtilityCss'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50'],
    'sql' => ['type' => 'boolean', 'default' => false],
];

PaletteManipulator::create()
    ->addLegend('utility_legend', 'style_legend', PaletteManipulator::POSITION_AFTER, true)
    ->addField('disableUtilityCss', 'utility_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_layout');
