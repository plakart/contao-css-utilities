<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_pcu_class_group']['title_legend'] = 'Gruppe';
$GLOBALS['TL_LANG']['tl_pcu_class_group']['target_legend'] = 'Sichtbarkeit';
$GLOBALS['TL_LANG']['tl_pcu_class_group']['publish_legend'] = 'Veröffentlichung';
$GLOBALS['TL_LANG']['tl_pcu_class_group']['title'] = ['Titel', 'Wird Redakteuren als Beschriftung des Auswahlfelds angezeigt.'];
$GLOBALS['TL_LANG']['tl_pcu_class_group']['alias'] = ['Alias', 'Fester Schlüssel der Gruppe (a-z, 0-9, - und _). Wird aus dem Titel erzeugt, wenn leer. Eine Änderung verwirft die Auswahl bestehender Datensätze beim nächsten Speichern.'];
$GLOBALS['TL_LANG']['tl_pcu_class_group']['multiple'] = ['Mehrfachauswahl', 'Redakteure dürfen mehrere Klassen dieser Gruppe wählen.'];
$GLOBALS['TL_LANG']['tl_pcu_class_group']['targets'] = ['Anzeigen bei', 'Wo Redakteure die Klassen dieser Gruppe auswählen können.'];
$GLOBALS['TL_LANG']['tl_pcu_class_group']['contentTypes'] = ['Inhaltselement-Typen', 'Gruppe auf diese Inhaltselemente beschränken. Leer lassen für alle Typen.'];
$GLOBALS['TL_LANG']['tl_pcu_class_group']['moduleTypes'] = ['Modul-Typen', 'Gruppe auf diese Module beschränken. Leer lassen für alle Typen.'];
$GLOBALS['TL_LANG']['tl_pcu_class_group']['formFieldTypes'] = ['Formularfeld-Typen', 'Gruppe auf diese Formularfelder beschränken. Leer lassen für alle Typen.'];
$GLOBALS['TL_LANG']['tl_pcu_class_group']['published'] = ['Veröffentlicht', 'Nur veröffentlichte Gruppen werden Redakteuren angeboten.'];
$GLOBALS['TL_LANG']['tl_pcu_class_group']['targetOptions'] = [
    'content' => 'Inhaltselemente',
    'article' => 'Artikel',
    'form_field' => 'Formularfelder',
    'module' => 'Frontend-Module',
    'page' => 'Seiten',
    'news' => 'Nachrichten',
    'event' => 'Events',
];
$GLOBALS['TL_LANG']['tl_pcu_class_group']['invalidAlias'] = 'Der Alias darf nur Kleinbuchstaben, Ziffern, Bindestriche und Unterstriche enthalten.';
$GLOBALS['TL_LANG']['tl_pcu_class_group']['pcuImport'] = ['Importieren', 'CSS-Klassen aus einer JSON-Datei importieren'];
$GLOBALS['TL_LANG']['tl_pcu_class_group']['pcuExport'] = ['Exportieren', 'CSS-Klassen als JSON-Datei exportieren'];
