# plakart/contao-css-utilities

Tailwind-style spacing utility classes for Contao 5. Editors assign margins and
paddings to content elements, articles, and form fields via four select fields —
no CSS knowledge required.

## How it works

- 36 fixed classes: `mt-0…8`, `mb-0…8`, `pt-0…8`, `pb-0…8`
- Values are fluid (`clamp()`) and defined as CSS custom properties
  `--space-1…--space-8`, so a theme can override the scale without renaming classes
- On save, the selected classes are merged into the element's CSS class
  (`cssID` / `class`); manually added classes are preserved
- `utilities.css` is loaded automatically on every page; each page layout has a
  "Disable utility CSS" checkbox to opt out

## Installation

```console
composer require plakart/contao-css-utilities
```

Then run the Contao install tool / migrations to add the new database columns.

## Overriding the scale

Define the custom properties in your theme after `utilities.css` is loaded:

```css
:root {
    --space-4: 1.25rem;
}
```

## Development

```console
composer test          # PHPUnit
composer phpstan       # static analysis
composer cs-fix        # code style
composer generate-css  # regenerate public/css/utilities.css (never edit by hand)
```
