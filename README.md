# plakart/contao-css-utilities

CSS classes for editors in Contao 5.7+ and 6, without CSS knowledge:

- **Spacing utilities:** Tailwind-style margins and paddings (`mt-4`, `pb-2` …) via
  four select fields on content elements, articles and form fields. The bundle
  ships the CSS.
- **CSS class selection:** groups of named CSS classes (e.g. "Button style",
  "Article width") that you maintain in the back end. Editors pick them via select
  fields on content elements, articles, form fields, modules, pages, news and
  events. Your theme provides the CSS.

## Installation

```console
composer require plakart/contao-css-utilities
```

Then run the Contao migrations (`contao:migrate` or the Contao Manager) to add the
database tables and columns.

The utility fields and the class selection field (`pcuClasses`) are `exclude`d by
default — grant them to non-admin user groups in the back-end permissions. The
module "Layout → CSS classes" needs the module permission as well.

## Spacing utilities

- 52 fixed classes: `mt-0…12`, `mb-0…12`, `pt-0…12`, `pb-0…12`
- Values are fluid (`clamp()`) and defined as CSS custom properties
  `--pcu-space-1…--pcu-space-12`, so a theme can override the scale without renaming classes
- `utilities.css` is loaded automatically on every page (classic and modern Twig
  layouts); each page layout has a "Disable utility CSS" checkbox to opt out

Override the scale in your theme after `utilities.css` is loaded:

```css
:root {
    --pcu-space-4: 1.25rem;
}
```

## CSS class selection

### Groups and classes

In **Layout → CSS classes** you create groups, and classes within each group.

| Group setting | Meaning |
|---|---|
| Title | Label of the select field editors see |
| Alias | Stable key (`a-z`, `0-9`, `-`, `_`), generated from the title. Selections are stored under it — **changing it drops the selection of existing records on their next save** |
| Multiple choice | Multiple select instead of a single select |
| Show in | Content elements, articles, form fields, modules, pages, news, events (news and events only if the bundle is installed) |
| Types | For content elements, modules and form fields: limit to certain types, e.g. only "Hyperlink". Empty = all types |
| Published | Only published groups and classes are offered |

A class can consist of several classes separated by spaces (`btn btn-primary`).
Groups appear in alphabetical order, classes in the order of the list.

### How classes reach the front end

On save, the selected classes are written into the record's CSS class field
(`cssID` for content elements, articles and modules; `class` for form fields;
`cssClass` for pages, news and events), together with the spacing utilities.
Classes typed in by hand are kept.

Records keep their classes until they are saved again. That means:

- If you rename or delete a class in the module, existing records keep the old class
  until they are re-saved.
- If a record's type changes (e.g. text → hyperlink), selections of groups that no
  longer apply are removed on save.

### Preset file, import and export

Groups and classes can be exchanged as JSON — see [docs/preset-format.md](docs/preset-format.md).

- **Export:** "Export" in the module; choose groups and classes, download a JSON file.
- **Import:** "Import" in the module; upload a JSON file or load the theme preset
  file, then choose in a preview what to import. Existing groups (same alias) and
  classes (same class in that group) are recognised and never changed or duplicated.
- **On install:** if the project contains the preset file and no group exists yet,
  `contao:migrate` imports it once. If you later delete all groups and the file still
  exists, the next migration imports it again. An invalid file is skipped by the
  migration; the import in the back end shows the error.

The preset file defaults to `files/theme/css-classes.json`. Change it in
`config/config.yaml`:

```yaml
plakart_css_utilities:
    preset_file: 'files/my-theme/css-classes.json' # relative to the project directory
```

## Development

```console
composer test          # PHPUnit (needs pdo_sqlite)
composer phpstan       # static analysis
composer cs-fix        # code style
composer generate-css  # regenerate public/css/utilities.css (never edit by hand)
```
