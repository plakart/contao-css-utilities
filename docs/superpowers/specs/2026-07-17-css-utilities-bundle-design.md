# Design: plakart/contao-css-utilities

Date: 2026-07-17
Status: approved

## Purpose

A Contao 5 bundle providing Tailwind-style spacing utility CSS classes (margins and
paddings) that editors assign to content elements, articles, and form fields via
backend select fields — modeled on the assignment idea of
`erdmannfreunde/theme-toolbox`, but with a fixed class set shipped by the bundle.

## Decisions (from brainstorming)

| Topic | Decision |
|---|---|
| Class source | Fixed set shipped by the bundle (no project config) |
| Class set | `mt`, `mb`, `pt`, `pb` × scale `0…8` → 36 classes, Tailwind-style names (`mt-4`) |
| Scale values | Fluid via `clamp()`; step 0 = `0`; defined as CSS custom properties `--space-1…--space-8` so themes can override values without renaming classes |
| Specificity | Declarations use `!important` so utilities win over theme CSS |
| Responsive variants | None (single scale; fluid values handle viewport scaling) |
| Backend UI | 4 select fields per record: margin-top, margin-bottom, padding-top, padding-bottom; options `0…8` plus empty option (= no class) |
| Target tables | `tl_content`, `tl_article`, `tl_form_field` |
| Output mechanism | Save callback merges selected classes into the record's class attribute (`cssID[1]` for content/article, `class` for form fields); own DB fields remain the source of the selection |
| CSS delivery | Loaded automatically on every page via `generatePage` hook; new checkbox `disableUtilityCss` in `tl_layout` (expert section) disables it per layout |
| Package | `plakart/contao-css-utilities`, namespace `Plakart\CssUtilitiesBundle`, Contao `^5.0`, PHP `>=8.2` |
| Language | English identifiers/comments/commits; backend labels in `en` + `de` |

## Architecture

```
contao-plakart-css-utilities/
├── composer.json                  (type contao-bundle, PSR-4 Plakart\CssUtilitiesBundle\)
├── config/services.yaml
├── contao/
│   ├── dca/tl_content.php
│   ├── dca/tl_article.php
│   ├── dca/tl_form_field.php
│   ├── dca/tl_layout.php          (disableUtilityCss checkbox)
│   └── languages/{en,de}/         (field labels)
├── public/css/utilities.css       (generated — do not edit by hand)
├── bin/generate-css.php           (regenerates utilities.css from UtilityClasses)
├── src/
│   ├── PlakartCssUtilitiesBundle.php
│   ├── ContaoManager/Plugin.php
│   ├── Util/UtilityClasses.php
│   ├── EventListener/DataContainer/UtilityClassSaveListener.php
│   └── EventListener/GeneratePageListener.php
└── tests/
```

### Components

- **`Util\UtilityClasses`** — single source of truth. Static definition of
  properties (`mt`, `mb`, `pt`, `pb`), scale steps (0–8), and the resulting class
  names. Consumed by: DCA option callbacks, the save listener (strip list), and
  the CSS generator. Also defines the `clamp()` value per step for generation.
- **`UtilityClassSaveListener`** — one `#[AsCallback]` save callback per table's
  four fields. On save: read all four utility selections of the current record,
  strip every known utility class from the class attribute, append the selected
  ones. Pure string logic, idempotent, leaves foreign classes untouched. Empty
  selection removes all utility classes.
- **`GeneratePageListener`** — `generatePage` hook; adds
  `bundles/plakartcssutilities/css/utilities.css` to `$GLOBALS['TL_CSS']` unless
  the active layout has `disableUtilityCss` checked.
- **DCA fields** — `utilityMt`, `utilityMb`, `utilityPt`, `utilityPb` (varchar,
  select with empty option) in a `{utility_legend:hide}` section appended to all
  palettes of the three tables. `tl_layout.disableUtilityCss` checkbox in the
  expert section.

## Edge cases

- Re-saving never duplicates classes (strip before append).
- Manually added classes in `cssID`/`class` are preserved.
- Clearing all four selects removes all utility classes from the attribute.
- If the scale changes in a future version, existing records keep old class
  strings until re-saved — accepted trade-off of the save-callback approach.

## Testing & QA

- PHPUnit: `UtilityClasses` completeness (36 classes, expected names) and the
  merge/strip logic of the save listener (duplicates, foreign classes, clearing).
- PHPStan + PHP-CS-Fixer (Contao ruleset) wired as composer scripts
  (`composer cs-fix`, `composer phpstan`, `composer test`).
- `bin/generate-css.php` keeps `utilities.css` and PHP definitions in sync; a
  test asserts the committed CSS matches the generator output.
