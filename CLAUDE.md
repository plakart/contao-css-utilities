# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Greenfield Contao bundle — **not yet scaffolded**. The directory is empty apart from this file; the first task is creating the bundle skeleton (composer.json, `src/`, `contao/`, `config/`).

- Intended package: `plakart/contao-css-utilities` (working title; directory name `contao-utilities-classes-bundle` is outdated — don't treat it as the package name)
- PHP namespace: `Plakart\CssUtilitiesBundle`
- Purpose: provide Tailwind-style utility CSS classes (margins, paddings, etc.) that editors can assign to Contao elements via DCA fields, modeled on `erdmannfreunde/theme-toolbox`
- Targets: Contao `^5.0`, PHP `>=8.2`

## Conventions

- English everywhere: identifiers, docblocks, comments, commit messages
- Code style: PHP-CS-Fixer with the Contao ruleset (`contao/code-quality` or `contao/easy-coding-standard`)
- Static analysis: PHPStan; tests: PHPUnit — wire both into composer scripts when scaffolding so they run as `composer cs-fix`, `composer phpstan`, `composer test`
- Development happens on Windows 11 / PowerShell — don't rely on symlinks or Unix-only tooling in scripts
