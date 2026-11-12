<?php

declare(strict_types=1);

$GLOBALS['BE_MOD']['design']['pcu_css_classes'] = [
    'tables' => ['tl_pcu_class_group', 'tl_pcu_class'],
];

$GLOBALS['BE_FFL']['pcuClassSelect'] = Plakart\CssUtilitiesBundle\Widget\ClassSelectionWidget::class;
