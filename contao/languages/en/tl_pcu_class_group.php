<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_pcu_class_group']['title_legend'] = 'Group';
$GLOBALS['TL_LANG']['tl_pcu_class_group']['target_legend'] = 'Visibility';
$GLOBALS['TL_LANG']['tl_pcu_class_group']['publish_legend'] = 'Publish';
$GLOBALS['TL_LANG']['tl_pcu_class_group']['title'] = ['Title', 'Shown to editors as the label of the select field.'];
$GLOBALS['TL_LANG']['tl_pcu_class_group']['alias'] = ['Alias', 'Stable key of the group (a-z, 0-9, - and _). Generated from the title if empty. Changing it drops the selection of existing records on their next save.'];
$GLOBALS['TL_LANG']['tl_pcu_class_group']['multiple'] = ['Multiple choice', 'Editors may select several classes of this group.'];
$GLOBALS['TL_LANG']['tl_pcu_class_group']['targets'] = ['Show in', 'Where editors can select the classes of this group.'];
$GLOBALS['TL_LANG']['tl_pcu_class_group']['contentTypes'] = ['Content element types', 'Restrict the group to these content element types. Leave empty for all types.'];
$GLOBALS['TL_LANG']['tl_pcu_class_group']['moduleTypes'] = ['Module types', 'Restrict the group to these module types. Leave empty for all types.'];
$GLOBALS['TL_LANG']['tl_pcu_class_group']['formFieldTypes'] = ['Form field types', 'Restrict the group to these form field types. Leave empty for all types.'];
$GLOBALS['TL_LANG']['tl_pcu_class_group']['published'] = ['Published', 'Only published groups are offered to editors.'];
$GLOBALS['TL_LANG']['tl_pcu_class_group']['targetOptions'] = [
    'content' => 'Content elements',
    'article' => 'Articles',
    'form_field' => 'Form fields',
    'module' => 'Front end modules',
    'page' => 'Pages',
    'news' => 'News',
    'event' => 'Events',
];
$GLOBALS['TL_LANG']['tl_pcu_class_group']['invalidAlias'] = 'The alias may only contain lowercase letters, digits, hyphens and underscores.';
$GLOBALS['TL_LANG']['tl_pcu_class_group']['pcuImport'] = ['Import', 'Import CSS classes from a JSON file'];
$GLOBALS['TL_LANG']['tl_pcu_class_group']['pcuExport'] = ['Export', 'Export CSS classes as a JSON file'];
$GLOBALS['TL_LANG']['tl_pcu_class_group']['pcuSpacing'] = ['Additional spacing', 'Manage additional spacing steps (13+)'];
