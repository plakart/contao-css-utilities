# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

- Package: `plakart/contao-css-utilities` (public), PHP namespace `Plakart\CssUtilitiesBundle`
- Purpose: CSS classes for Contao editors — fixed spacing utilities (`mt-*`, `mb-*`, `pt-*`, `pb-*`, CSS shipped) and, since 2.0, a back-end managed CSS class selection (groups → classes) modeled on the class selection of `erdmannfreunde/theme-toolbox` (functionality only, no code taken over)
- Targets: Contao `^5.7 || ^6.0`, PHP `^8.4`
- Design docs: `docs/superpowers/specs/`, plans: `docs/superpowers/plans/`, preset format: `docs/preset-format.md`

## Decisions that are easy to break

- **Preset format is a public contract.** The private theme template `plakart/contao-barebone` ships `files/theme/css-classes.json` in this format. Change it only with a new `version` and keep reading version 1.
- **Class selection field:** one blob column `pcuClasses` (serialized `alias => list of classes`) with the custom widget `pcuClassSelect`. Not Contao virtual fields: their field map is cached at warm-up, so fields depending on DB rows would be missing in prod.
- **Target tables are extended in `LoadDataContainerListener`**, not in `contao/dca/*.php`: a DCA file for `tl_news` would create a partial table when the news bundle is missing. The hook must not query the database (it runs during schema building).
- **One onload (`ClassSelectionPaletteListener`) and one onsubmit (`ClassAttributeListener`) per target table**, registered as array callbacks (services are `public`). The onload also applies the spacing palette so the legend order is fixed.
- **`PresetFileMigration::shouldRun()` must turn false after `run()`**, otherwise `contao:migrate` loops. Invalid or empty preset files never run.
- **Modern (Twig) layouts skip the `generatePage` hook**; `utilities.css` is also added via `LayoutEvent`.
- **Contao 6 returns `RecordLabel` from label callbacks**; `UtilityClassLabelListener` must keep supporting string, array and `RecordLabel` (the class does not exist in 5.7, PHPStan ignores it there).
- **Back-end templates live in `contao/templates/` with a `.twig-root` marker**, otherwise Contao flattens `backend/pcu_css_classes/*` to `@Contao/*.html.twig`.
- **Back-end forms of the import/export use `data-turbo="false"`** (file download, non-redirect POST responses).

## Conventions

- English everywhere: identifiers, docblocks, comments, commit messages
- Code style: PHP-CS-Fixer (`@Symfony` + PHP 8.4 migration rules), static analysis: PHPStan, tests: PHPUnit (`contao/test-case`, SQLite in-memory for DBAL tests) — run as `composer cs-fix`, `composer phpstan`, `composer test`
- Check both Contao versions when touching core APIs: `composer update --with contao/core-bundle:5.7.*` / `:6.0.*`; compare core sources via `gh api "repos/contao/contao/contents/<path>?ref=5.7"`
- Development happens on Windows 11 / PowerShell — don't rely on symlinks or Unix-only tooling in scripts
