# Design: CSS class selection (version 2.0)

Date: 2026-11-12
Status: implemented (2.0)

## Purpose

Version 2.0 extends `plakart/contao-css-utilities` from fixed spacing utilities to a
general **CSS class selection**: admins maintain groups of human-readable CSS
classes in the back end, editors pick them via select fields on content elements,
articles, form fields, modules, pages, news and events. The feature mirrors the
class-selection part of `erdmannfreunde/theme-toolbox` (functionality only, no
code taken over).

Default classes (buttons, article width, image position …) belong to the private
theme template `plakart/contao-barebone`. The barebone ships them as
`files/theme/css-classes.json`; the bundle imports that file once on install via a
migration and offers a back-end import/export for later updates.

## Decisions

| Topic | Decision |
|---|---|
| Scope | Only the class selection of theme-toolbox. No theme/SCSS editor, webfonts, images, JS editor, live editor |
| Spacing utilities | Unchanged: four dedicated select fields + shipped `utilities.css` on `tl_content`, `tl_article`, `tl_form_field` |
| Class source | Database, editable in a new back-end module "CSS classes" (section Layout/design) |
| Data model | Group (`tl_pcu_class_group`) → classes (`tl_pcu_class`) |
| Group settings | Title, alias (stable key), single or multiple choice, targets, optional type restriction per target, published. Groups are listed alphabetically (Contao has no drag sorting for top-level tables in list mode) |
| Targets | `content` (tl_content.cssID), `article` (tl_article.cssID), `form_field` (tl_form_field.class), `module` (tl_module.cssID), `page` (tl_page.cssClass), `news` (tl_news.cssClass, only with `contao/news-bundle`), `event` (tl_calendar_events.cssClass, only with `contao/calendar-bundle`) |
| Type restriction | `content`, `module` and `form_field` can be restricted to element/module/field types; empty = all types |
| Not supported | FAQ (no CSS class column in core) |
| Editor UI | One field `pcuClasses` per target table with a custom widget that renders one select per applicable group (single or multiple) |
| Storage | `pcuClasses` blob column, serialized `array<groupAlias, list<className>>` |
| Output | `onsubmit` merges the selection into the record's class column, same mechanism as the spacing utilities; foreign classes are preserved |
| Preset format | JSON (`format: plakart-css-classes`, `version: 1`); export and barebone file share the same format |
| Preset file | Configurable, default `files/theme/css-classes.json` (relative to the project dir) |
| Install import | Migration imports the preset file once when the group table is empty |
| Later updates | Back-end import with preview; existing groups/classes are detected and never duplicated; they are only overwritten if "update" is checked per group or class |
| Export | All or selected groups/classes as JSON download |
| Versions | Contao `^5.7 \|\| ^6.0`, PHP `^8.4`, release 2.0.0 |
| Language | English code/docs; back-end labels `en` + `de` |

## Rejected alternatives

- **Hard-coding the barebone classes in the bundle:** couples a public bundle to a
  private theme; class names and CSS would live in two repos.
- **Contao virtual fields (one dynamic DCA field per group):** the virtual field map
  is written to `var/cache/…/contao/sql/` at cache warm-up, so fields that depend on
  database rows would be missing in production.
- **Dynamic proxy fields with load/save callbacks:** bypass the core save, so
  versioning and cache-tag invalidation would not run when only classes change.

## Architecture

```
src/
├── PlakartCssUtilitiesBundle.php      + configuration (preset_file)
├── Target/
│   ├── Target.php                     value object: key, table, column, cssIdIndex|null, typeField|null
│   └── TargetRegistry.php             all targets; filters by installed bundles
├── ClassSelection/
│   ├── ClassGroup.php                 value object: alias, title, multiple, targets, classes
│   ├── ClassGroupRepository.php       DBAL, published groups + classes, memoized per request
│   └── SelectionNormalizer.php        filters submitted values against allowed groups/classes
├── Widget/ClassSelectionWidget.php    BE_FFL "pcuClassSelect"
├── EventListener/
│   ├── LoadDataContainerListener.php  adds pcuClasses + callbacks to active target tables
│   ├── DataContainer/ClassSelectionPaletteListener.php
│   ├── DataContainer/ClassAttributeListener.php   (replaces UtilityClassSaveListener)
│   └── DataContainer/UtilityClassLabelListener.php (shows selected classes too)
├── Preset/
│   ├── Preset.php, PresetGroup.php, PresetClass.php
│   ├── PresetSerializer.php           JSON <-> Preset, validation
│   ├── PresetExporter.php             DB -> Preset (all or selected)
│   ├── PresetImporter.php             plan() and apply()
│   └── ImportPlan.php
├── Migration/PresetFileMigration.php
├── Controller/Backend/ExportController.php
├── Controller/Backend/ImportController.php
└── Util/ClassAttributeMerger.php      generic strip/append (UtilityClassMerger delegates)
contao/
├── config/config.php                  BE_MOD + BE_FFL
├── dca/tl_pcu_class_group.php
├── dca/tl_pcu_class.php
└── languages/{en,de}/…
templates/backend/pcu_css_classes/{import,export}.html.twig
```

### Data model

`tl_pcu_class_group` (module table, sortable list)

| Field | Type | Notes |
|---|---|---|
| `title` | varchar(255) | mandatory, shown to editors as select label |
| `alias` | varchar(64), unique | mandatory, `[a-z0-9_-]`, generated from title; key in `pcuClasses` and presets |
| `multiple` | boolean | multiple select instead of single select |
| `targets` | blob | checkbox list of target keys (only installed targets offered) |
| `contentTypes`, `moduleTypes`, `formFieldTypes` | blob | optional type restriction, options from `TL_CTE`, `FE_MOD`, `TL_FFL`; shown only if the target is checked |
| `published` | boolean | default true |

`tl_pcu_class` (child table, `ptable` = `tl_pcu_class_group`, sortable)

| Field | Type | Notes |
|---|---|---|
| `cssClass` | varchar(255) | mandatory, one or more class tokens matching `-?[_a-zA-Z][_a-zA-Z0-9-]*`, unique per group |
| `label` | varchar(255) | mandatory, option text for editors |
| `published` | boolean | default true |

### Selection field

- `LoadDataContainerListener` (`loadDataContainer` hook) adds to every **active**
  target table: field `pcuClasses` (`inputType: pcuClassSelect`, `exclude: true`,
  `sql: blob NULL`), an `onload` palette callback and an `onsubmit` class callback.
  Adding it in the hook instead of `contao/dca/tl_news.php` avoids creating a
  partial `tl_news` table when the news bundle is not installed.
- `ClassSelectionPaletteListener` (`onload`) resolves the record type and inserts
  `pcuClasses` only if at least one published group applies. Tables with spacing
  utilities get it into the existing `utility_legend`; others into a new
  `pcu_classes_legend` before `expert_legend` (appended if missing).
- `ClassSelectionWidget` renders one labelled select per applicable group
  (`multiple` per group). `validator()` passes the input through
  `SelectionNormalizer`, which drops unknown groups/classes and enforces single
  choice. Stored value: serialized `['buttons' => ['btn_hyperlink_primary']]`.

### Class output

`ClassAttributeListener` (`onsubmit`, all active targets) reads the saved record,
strips **all known classes** (spacing utilities + every `tl_pcu_class.cssClass`
token, published or not) from the class column and appends the current selection
(spacing first, then groups in sorting order). `cssID` keeps index 0 (ID). Uses
the generic `ClassAttributeMerger`.

### Preset format

```json
{
    "format": "plakart-css-classes",
    "version": 1,
    "groups": [
        {
            "alias": "buttons",
            "title": "Buttons",
            "multiple": false,
            "targets": {
                "content": ["hyperlink"]
            },
            "classes": [
                { "class": "btn_hyperlink_primary", "label": "Button primary" },
                { "class": "btn_hyperlink_secondary", "label": "Button secondary" }
            ]
        },
        {
            "alias": "article-width",
            "title": "Article width",
            "multiple": false,
            "targets": { "article": [] },
            "classes": [
                { "class": "full--width", "label": "Full width" }
            ]
        }
    ]
}
```

- `targets`: object keyed by target key; value = list of types, `[]` = all types.
  Unknown keys (e.g. `news` without the news bundle) are stored and ignored at
  runtime, so one preset fits every installation.
- Optional `published` on groups and classes, default `true`.
- `PresetSerializer` rejects: wrong `format`, unsupported `version`, missing
  alias/title/class/label, invalid alias or class tokens, duplicate group aliases,
  duplicate classes within a group. Errors name the JSON path (`groups[1].classes[0].class`).

### Import

- Matching: group by `alias`, class by `cssClass` within its group.
- `PresetImporter::plan(Preset)` → `ImportPlan` listing each group/class as `new`
  or `existing`.
- `PresetImporter::apply(Preset, selection, updates)` creates selected new groups
  (with their selected classes), appends selected new classes to existing groups and
  overwrites existing groups/classes only if listed in `updates` (title, multiple,
  targets incl. types, published; label, published). Alias and CSS class never
  change. Runs in one transaction.
- UI (`ImportController`, route `/contao/pcu-css-classes/import`):
  1. Upload a JSON file, or "Load preset file" (shown only if the configured file exists).
  2. Preview tree with checkboxes; new entries preselected, existing ones shown
     disabled with a hint. The preset JSON travels in a hidden field and is
     validated again on submit (no session state).
  3. Apply, flash message with counts, redirect to the module.

### Export

`ExportController` (route `/contao/pcu-css-classes/export`): checkbox tree of all
groups/classes (all preselected) → JSON download
`css-classes-YYYY-MM-DD.json`. Unpublished records are exported with `published: false`.

### Migration

`PresetFileMigration` (`AbstractMigration`):

- `shouldRun()`: preset file exists **and** `tl_pcu_class_group` exists **and**
  the table is empty **and** the file is valid **and** contains at least one group.
- `run()`: import everything, result message with counts.
- Contao re-runs pending migrations until none is left, so `shouldRun()` must turn
  false after `run()`: the table is no longer empty. An invalid or empty file never
  runs (otherwise migrate would loop); the import UI shows the validation error.
- If all groups are deleted later and the file still exists, the next migrate
  imports it again. Documented.

### Permissions

- Back-end module `pcu_css_classes` (section `design`); import/export controllers
  require `ContaoCorePermissions::USER_CAN_ACCESS_MODULE` for it.
- `pcuClasses` is `exclude`d like the spacing fields.

## Edge cases

- Class renamed or deleted in the module: records keep the old class until
  re-saved; the old token is stripped on the next save only while it is still
  defined somewhere (same trade-off as the spacing scale). Documented.
- Group alias changed: stored selections under the old alias are dropped on the
  next save. The alias field shows a warning when changed.
- Same token in two groups: allowed; output deduplicated.
- Record type changes (e.g. text → hyperlink): selections of groups that no
  longer apply are dropped by the normalizer on save.
- Manually typed class that equals a defined class: absorbed by the selection on
  save (same as spacing utilities).
- Modern and classic page layouts: unaffected, classes end up in the same columns.

## Testing & QA

- PHPUnit for target registry, normalizer, merger, serializer validation,
  exporter/importer (DBAL with in-memory SQLite), migration `shouldRun()` cases,
  widget validation, attribute listener.
- PHPStan level 6, PHP-CS-Fixer; existing composer scripts.
- Manual check on a Contao 6 (`~/projects/contao6`) and a Contao 5.7
  installation: install, migration import, editing, front-end output in classic
  and modern layouts, import/export round trip.

## Changes during implementation

- Groups are ordered by title, not by a manual sorting field.
- The class selection gets its own legend `pcu_classes_legend`, placed before the
  spacing legend (or before `expert_legend` on tables without spacing).
  `ClassSelectionPaletteListener` also applies the spacing fields, so the order does
  not depend on callback priorities; `UtilityClassSaveListener` was merged into
  `ClassAttributeListener`.
- Export selection: only checked groups are exported, and of those only the
  checked classes. Import selection: new groups need their own checkbox; existing
  groups only receive their checked new classes.
- Back-end templates need `contao/templates/.twig-root`.
- Found while testing on Contao 6: the card-header wrapper crashed on the
  `RecordLabel` returned by the core label callback (also affects 1.1.0).
- Import can update existing records on request: the preview lists the
  differences of existing groups and classes, each with an unchecked "update"
  checkbox. Without it, existing records stay unchanged (as before).
