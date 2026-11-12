# CSS class selection (2.0) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a back-end managed CSS class selection (groups → classes) that editors assign to content elements, articles, form fields, modules, pages, news and events; ship JSON import/export and a migration that imports `files/theme/css-classes.json` once on install.

**Architecture:** Two new tables (`tl_pcu_class_group`, `tl_pcu_class`) hold the classes. A `loadDataContainer` hook adds one blob field `pcuClasses` with a custom widget to every active target table; the widget renders one select per applicable group. An `onsubmit` listener merges spacing utilities and selected classes into the record's class column. A JSON preset format is shared by export, import and the install migration.

**Tech Stack:** PHP ^8.4, Contao `^5.7 || ^6.0`, Symfony `^7.4 || ^8.0`, Doctrine DBAL, PHPUnit 11/12, PHPStan, PHP-CS-Fixer.

**Spec:** `docs/superpowers/specs/2026-11-12-css-class-selection-design.md`

## Global Constraints

- Keep 1.x behaviour of the spacing utilities intact (fields, CSS, layout opt-out, card badge)
- Target keys exactly: `content`, `article`, `form_field`, `module`, `page`, `news`, `event`
- Preset `format` = `plakart-css-classes`, `version` = `1`
- Config key: `plakart_css_utilities.preset_file`, default `files/theme/css-classes.json`, relative to `%kernel.project_dir%`
- English identifiers, comments, commit messages; back-end labels `en` + `de`
- TDD for every class with logic: failing test → implementation → green
- After every task: `composer test`, `composer phpstan`, `composer cs-fix`, then commit (conventional commits, footer `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`)
- PHP is not installed in WSL: run QA via Docker, e.g. `docker run --rm -v "$PWD":/app -w /app composer:2 composer test` (add `--ignore-platform-reqs` to `composer update`); verify against Contao 6.0 and 5.7 with `composer update --with contao/core-bundle:6.0.*` / `:5.7.*`
- Where Contao 5.7 and 6 differ (template paths, widget markup, back-end controller base), check both versions in `~/projects/contao6/vendor/contao` and `gh api "repos/contao/contao/contents/…?ref=5.7"` before writing code

---

### Task 1: Bundle configuration and target registry

**Files:**
- Modify: `src/PlakartCssUtilitiesBundle.php` (add `configure()` with `preset_file`, expose as parameter `plakart_css_utilities.preset_file` resolved against `%kernel.project_dir%`)
- Create: `src/Target/Target.php`, `src/Target/TargetRegistry.php`
- Create: `tests/Target/TargetRegistryTest.php`
- Modify: `config/services.yaml` (bind `$bundles: '%kernel.bundles%'`, `$presetFile`)

**Interfaces:**
- `Target` (readonly): `key`, `table`, `column` (`cssID` | `cssClass` | `class`), `isCssId(): bool`, `typeField: ?string` (`type` for content/module/form_field, else null), `requiredBundle: ?string`
- `TargetRegistry::__construct(array $bundles)`; `all(): array<string, Target>` (only installed); `get(string $key): Target`; `forTable(string $table): ?Target`; `has(string $key): bool`

- [ ] **Step 1:** Test: without `ContaoNewsBundle`/`ContaoCalendarBundle` in `$bundles` → keys `content, article, form_field, module, page`; with both → plus `news`, `event`; `forTable('tl_news')` null when news bundle missing; columns and type fields as in the spec table
- [ ] **Step 2:** Run, see it fail
- [ ] **Step 3:** Implement `Target`, `TargetRegistry` (static definition list), bundle configuration
- [ ] **Step 4:** Green, QA, commit `feat: add target registry and preset file configuration`

### Task 2: Generic class attribute merger

**Files:**
- Create: `src/Util/ClassAttributeMerger.php`, `tests/Util/ClassAttributeMergerTest.php`
- Modify: `src/Util/UtilityClassMerger.php` (delegate, keep public API)

**Interfaces:**
- `ClassAttributeMerger::merge(string $current, list<string> $strip, list<string> $add): string` — tokenises on whitespace, removes every token contained in `$strip` (multi-token entries are split), appends `$add` tokens, deduplicates, keeps order of foreign classes, single spaces

- [ ] **Step 1:** Tests: foreign classes kept; stripped tokens removed; multi-token class `"btn btn-primary"` stripped and added token-wise; duplicates removed; empty input/empty selection; whitespace normalised
- [ ] **Step 2–4:** Fail → implement → green; existing `UtilityClassMergerTest` stays green
- [ ] **Step 5:** QA, commit `refactor: extract generic class attribute merger`

### Task 3: Data model and back-end module

**Files:**
- Create: `contao/dca/tl_pcu_class_group.php`, `contao/dca/tl_pcu_class.php`
- Create: `contao/config/config.php` (`$GLOBALS['BE_MOD']['design']['pcu_css_classes'] = ['tables' => ['tl_pcu_class_group', 'tl_pcu_class']]`)
- Create: `contao/languages/{en,de}/tl_pcu_class_group.php`, `tl_pcu_class.php`, `modules.php`
- Create: `src/EventListener/DataContainer/ClassGroupOptionsListener.php` (options for `targets` from `TargetRegistry`, types from `TL_CTE` / `FE_MOD` / `TL_FFL`), `src/EventListener/DataContainer/ClassGroupAliasListener.php` (generate alias from title via `contao.slug`, uniqueness)
- Create: `tests/EventListener/DataContainer/ClassGroupOptionsListenerTest.php`

**Details:**
- Fields and types as in the spec "Data model" tables; `targets` is a checkbox list with `submitOnChange`; sub-palettes show `contentTypes` / `moduleTypes` / `formFieldTypes` (select multiple, `chosen`) only when the matching target is checked (use a palette `onload` callback, `targets` is multi-value)
- `tl_pcu_class.cssClass` validation via `save_callback`: tokens must match `/^-?[_a-zA-Z][_a-zA-Z0-9-]*$/`; unique within `pid`
- List views: groups need a manual order (it is the order of the selects for editors) — check which list mode allows drag sorting of a top-level table in 5.7 and 6; fallback `sorting.mode = 1` ordered by `title`; classes `sorting.mode = 4` (parent view) sorted by `sorting`, `headerFields = ['title', 'alias']`, label `"%s <span class=\"tl_gray\">.%s</span>"` (label, cssClass)
- Toggle operation for `published` on both tables

- [ ] **Step 1:** Tests for the options listener (installed targets only, type options from globals, grouped like the core `type` select)
- [ ] **Step 2–4:** Fail → implement listeners → green
- [ ] **Step 5:** Write DCA + language files; manual check in `~/projects/contao6`: `contao:migrate` creates both tables, module appears under "Layout", records can be created
- [ ] **Step 6:** QA, commit `feat: add CSS class group and class tables`

### Task 4: Group repository

**Files:**
- Create: `src/ClassSelection/ClassGroup.php`, `src/ClassSelection/ClassGroupRepository.php`
- Create: `tests/ClassSelection/ClassGroupRepositoryTest.php` (DBAL `pdo_sqlite` in-memory; add `ext-pdo_sqlite` hint to README development section)

**Interfaces:**
- `ClassGroup` (readonly): `id`, `alias`, `title`, `multiple`, `array<string, list<string>> $targets` (key → types, `[]` = all), `array<string, string> $classes` (cssClass → label, sorted)
- `ClassGroupRepository::findPublishedFor(string $targetKey, ?string $type): list<ClassGroup>` — published groups whose targets include the key and (no restriction or type listed), only published classes, groups without published classes omitted
- `ClassGroupRepository::findAllClassTokens(): list<string>` — every `cssClass` token, published or not (strip list)
- Memoized per instance; implement `Symfony\Contracts\Service\ResetInterface`

- [ ] **Step 1:** Tests: target filter, type restriction (empty = all), unpublished group/class excluded, empty group excluded, order by `sorting`, all tokens incl. unpublished and multi-token split
- [ ] **Step 2–4:** Fail → implement → green
- [ ] **Step 5:** QA, commit `feat: add class group repository`

### Task 5: Selection normalizer and widget

**Files:**
- Create: `src/ClassSelection/SelectionNormalizer.php`, `tests/ClassSelection/SelectionNormalizerTest.php`
- Create: `src/Widget/ClassSelectionWidget.php`, `tests/Widget/ClassSelectionWidgetTest.php`
- Modify: `contao/config/config.php` (`$GLOBALS['BE_FFL']['pcuClassSelect'] = ClassSelectionWidget::class`)

**Interfaces:**
- `SelectionNormalizer::normalize(mixed $input, list<ClassGroup> $groups): array<string, list<string>>` — accepts array or serialized string; drops unknown aliases/classes; single-choice groups keep the first value; removes empty groups; order follows `$groups`
- `ClassSelectionWidget extends Widget`: `$blnSubmitInput = true`; gets its groups from `ClassGroupRepository` via the container using `$this->dataContainer` (table → target, record type via `$this->activeRecord`); `generate()` renders per group a `<label>` + `<select name="pcuClasses[alias]">` (or `[alias][]` + `multiple`), empty option for single choice; `validator()` → normalizer → serialized array

- [ ] **Step 1:** Normalizer tests (all rules above, serialized input, garbage input → `[]`)
- [ ] **Step 2–4:** Fail → implement → green
- [ ] **Step 5:** Compare markup with the core `SelectMenu` widget in 5.7 and 6 (class names, `chosen`/choices.js attributes) and reuse them so the selects look native; widget test for the rendered names, `multiple` and selected options
- [ ] **Step 6:** QA, commit `feat: add class selection widget`

### Task 6: Register field, palettes and class output on all targets

**Files:**
- Create: `src/EventListener/LoadDataContainerListener.php` (`#[AsHook('loadDataContainer')]`)
- Create: `src/EventListener/DataContainer/ClassSelectionPaletteListener.php`
- Create: `src/EventListener/DataContainer/ClassAttributeListener.php` (replaces `UtilityClassSaveListener`)
- Delete: `src/EventListener/DataContainer/UtilityClassSaveListener.php` and its test (move cases to the new test)
- Create: tests for all three
- Modify: language files of the target tables (`pcuClasses`, `pcu_classes_legend`), add `tl_module`, `tl_page`, `tl_news`, `tl_calendar_events` language files

**Details:**
- Hook: if `TargetRegistry::forTable($table)` exists → add field `pcuClasses` (`inputType: pcuClassSelect`, `exclude: true`, `eval: ['tl_class' => 'clr']`, `sql: ['type' => 'blob', 'length' => 65535, 'notnull' => false]`), append `onload_callback` `[ClassSelectionPaletteListener::class, '__invoke']` and `onsubmit_callback` `[ClassAttributeListener::class, '__invoke']` (service ids = class names, make both `public: true` or tag-free callable via `System::importStatic`). The hook must not query the database (schema building runs it too)
- Palette listener: load the record type via `$dc->id` (DBAL), ask the repository; if no group applies → do nothing; otherwise add `pcuClasses` to `utility_legend` (tables with spacing fields) or a new `pcu_classes_legend` before `expert_legend` on all string palettes. Keep `UtilityPaletteListener` order so the spacing fields stay first
- Attribute listener: fetch row; spacing selection (only for the three utility tables) + `pcuClasses` normalized against the groups for the current type; strip list = `UtilityClasses::all()` + `findAllClassTokens()`; write `cssID[1]`, `cssClass` or `class` via DBAL; skip the update if the value is unchanged
- Edge cases from the spec covered by tests: foreign classes kept, type change drops stale groups, unknown alias dropped, `cssID[0]` untouched, news/event tables ignored when bundle missing

- [ ] **Step 1:** Tests for hook (field added only for active targets, nothing for `tl_news` without bundle), palette listener (legend placement, no field without groups), attribute listener (each column type)
- [ ] **Step 2–4:** Fail → implement → green
- [ ] **Step 5:** Manual check in `~/projects/contao6`: hyperlink element shows "Buttons" select, text element does not; saving writes classes into `cssID`; front end shows them in classic and modern layout
- [ ] **Step 6:** QA, commit `feat: assign CSS classes to all target tables`

### Task 7: Card header badge

**Files:**
- Modify: `src/EventListener/DataContainer/UtilityClassLabelListener.php`, its test

- [ ] **Step 1:** Test: badge lists spacing utilities and selected group classes (from `pcuClasses`) in one span, no badge when both empty
- [ ] **Step 2–4:** Fail → implement → green
- [ ] **Step 5:** QA, commit `feat: show selected CSS classes in the element card header`

### Task 8: Preset model and serializer

**Files:**
- Create: `src/Preset/Preset.php`, `PresetGroup.php`, `PresetClass.php`, `PresetSerializer.php`, `InvalidPresetException.php`
- Create: `tests/Preset/PresetSerializerTest.php`, fixtures `tests/Fixtures/presets/*.json`

**Interfaces:**
- `PresetSerializer::fromJson(string $json): Preset` (throws `InvalidPresetException` with JSON path); `toJson(Preset $preset): string` (pretty print, unescaped slashes/unicode, trailing newline)
- `PresetGroup`: `alias`, `title`, `multiple`, `targets: array<string, list<string>>`, `published`, `list<PresetClass> $classes`; `PresetClass`: `class`, `label`, `published`

- [ ] **Step 1:** Tests: round trip of the spec example; defaults for optional `published`/`multiple`; each validation rule from the spec with its JSON path; unknown target keys accepted; invalid JSON
- [ ] **Step 2–4:** Fail → implement → green
- [ ] **Step 5:** QA, commit `feat: add CSS class preset format`

### Task 9: Exporter and importer

**Files:**
- Create: `src/Preset/PresetExporter.php`, `PresetImporter.php`, `ImportPlan.php`
- Create: `tests/Preset/PresetExporterTest.php`, `PresetImporterTest.php` (SQLite)

**Interfaces:**
- `PresetExporter::export(?list<int> $groupIds = null, ?list<int> $classIds = null): Preset` — `null` = all; a selected class implies its group
- `ImportPlan`: per group `alias`, `isNew`, classes with `isNew`
- `PresetImporter::plan(Preset): ImportPlan`
- `PresetImporter::apply(Preset, ?array $selection = null): array{groups: int, classes: int}` — selection `array<alias, list<class>>`, `null` = everything new; creates groups at the end of the sorting, classes appended; never updates existing rows; one transaction; generated `tstamp`

- [ ] **Step 1:** Tests: export all / selection; plan marks existing by alias and class; apply creates only new, appends to existing groups, respects selection, second apply is a no-op, rollback on failure
- [ ] **Step 2–4:** Fail → implement → green
- [ ] **Step 5:** QA, commit `feat: add CSS class import and export services`

### Task 10: Install migration

**Files:**
- Create: `src/Migration/PresetFileMigration.php`, `tests/Migration/PresetFileMigrationTest.php`

**Details:** `shouldRun()` true only if file exists, table exists (`createSchemaManager()->tablesExist`), table empty, file valid, at least one group. `run()` → `PresetImporter::apply()`, message "Imported X groups and Y classes from files/theme/css-classes.json".

- [ ] **Step 1:** Tests: each false case (no file, no table, not empty, invalid, no groups), true case, `run()` imports and `shouldRun()` is false afterwards
- [ ] **Step 2–4:** Fail → implement → green
- [ ] **Step 5:** Manual check: copy a preset to `~/projects/contao6/files/theme/css-classes.json`, run `contao:migrate`, records exist, second run reports nothing pending
- [ ] **Step 6:** QA, commit `feat: import the theme preset file on install`

### Task 11: Back-end import and export

**Files:**
- Create: `src/Controller/Backend/ExportController.php`, `ImportController.php`
- Create: `templates/backend/pcu_css_classes/export.html.twig`, `import.html.twig`, `_tree.html.twig`
- Create: `config/routes.yaml` + route loading in `ContaoManager/Plugin.php` (`RoutingPluginInterface`)
- Modify: `contao/dca/tl_pcu_class_group.php` (global operations `import`, `export` linking to the routes)
- Modify: language files (`MSC`/module labels, flash messages)
- Create: controller tests (access denied without module permission, export JSON response headers and content, import preview and apply happy path, invalid upload shows error)

**Details:**
- Base class: `Contao\CoreBundle\Controller\AbstractBackendController`; render into the back-end main template — check the template name/blocks in 5.7 (`templates/twig/…`) and 6 (`templates/…`) first
- Access: `denyAccessUnlessGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'pcu_css_classes')`
- CSRF: Contao request token in every POST form
- Import flow: step 1 upload (`.json`, max 1 MB) or "Load preset file" button (only if the configured file exists); step 2 preview tree from `ImportPlan` (new checked, existing disabled + hint), preset JSON in a hidden field; step 3 re-validate, `apply()`, flash message with counts, redirect to `pcu_css_classes`
- Export: checkbox tree (all checked) → `Response` with `Content-Type: application/json`, `Content-Disposition: attachment; filename="css-classes-YYYY-MM-DD.json"`

- [ ] **Step 1:** Controller tests
- [ ] **Step 2–4:** Fail → implement → green
- [ ] **Step 5:** Manual round trip in `~/projects/contao6`: export → delete a class → import shows it as new, rest as existing → apply
- [ ] **Step 6:** QA, commit `feat: add back-end import and export for CSS classes`

### Task 12: Docs, release prep, cross-version check

**Files:**
- Modify: `README.md` (new "CSS class selection" section: module, targets, preset file, JSON format, import/export, migration behaviour, permissions, edge cases), `CLAUDE.md` (purpose now covers the class selection; preset format is a public contract shared with `plakart/contao-barebone`), `composer.json` description
- Create: `docs/preset-format.md` (normative description of the JSON format, referenced from the barebone issue)

- [ ] **Step 1:** Write docs
- [ ] **Step 2:** Full QA on Contao 6.0 and 5.7 (Docker, see constraints)
- [ ] **Step 3:** Manual smoke test on both versions: fresh install with barebone preset, editor without admin rights (field and module permissions), classic + modern layout output
- [ ] **Step 4:** Commit `docs: document the CSS class selection`; release `2.0.0` only when the user asks
