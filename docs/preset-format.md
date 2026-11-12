# CSS class preset format (version 1)

The JSON format used by the back-end export, the back-end import and the theme
preset file (`files/theme/css-classes.json` by default). A file exported from the
back end can be used as a theme preset file as is.

## Example

```json
{
    "format": "plakart-css-classes",
    "version": 1,
    "groups": [
        {
            "alias": "buttons",
            "title": "Button style",
            "multiple": false,
            "targets": {
                "content": ["hyperlink"]
            },
            "classes": [
                { "class": "btn_hyperlink_primary", "label": "Primary button" },
                { "class": "btn_hyperlink_secondary", "label": "Secondary button" }
            ]
        },
        {
            "alias": "article-width",
            "title": "Article width",
            "targets": { "article": [] },
            "classes": [
                { "class": "full--width", "label": "Full width" }
            ]
        }
    ]
}
```

## Fields

### Root

| Key | Required | Value |
|---|---|---|
| `format` | yes | Always `"plakart-css-classes"` |
| `version` | yes | Always `1` |
| `groups` | no | List of groups, default `[]` |
| `spacing` | no | List of additional spacing steps, default `[]` |

### Group

| Key | Required | Value |
|---|---|---|
| `alias` | yes | Stable key, `a-z`, `0-9`, `-`, `_`, max. 64 characters, unique within the file. Imports match existing groups by alias. **Do not rename** — records store their selection under it |
| `title` | yes | Label of the select field |
| `multiple` | no | `true` = several classes of the group can be selected, default `false` |
| `targets` | no | Object: target key → list of types. `[]` = all types. Default `{}` (group is not shown anywhere) |
| `classes` | no | List of classes, default `[]` |
| `published` | no | Default `true` |

Target keys:

| Key | Records | Types |
|---|---|---|
| `content` | Content elements | Content element types, e.g. `hyperlink`, `text` |
| `article` | Articles | — |
| `form_field` | Form fields | Form field types, e.g. `text`, `submit` |
| `module` | Front-end modules | Module types, e.g. `navigation` |
| `page` | Pages | — |
| `news` | News (needs `contao/news-bundle`) | — |
| `event` | Events (needs `contao/calendar-bundle`) | — |

Types are ignored for targets without types. Unknown target keys are kept and
ignored at runtime, so one file fits installations with and without news or events.

### Spacing step

Additional spacing steps (13+) for the four spacing selects. The theme provides their
CSS; the bundle only adds the select options.

| Key | Required | Value |
|---|---|---|
| `step` | yes | Whole number from 13, unique within the file. Imports match existing steps by it |
| `label` | no | Shown next to the number in the selects, default `""` |

```json
"spacing": [
    { "step": 13, "label": "Hero section" },
    { "step": 16 }
]
```

### Class

| Key | Required | Value |
|---|---|---|
| `class` | yes | One or more CSS classes without a leading dot, separated by spaces. Each must match `-?[_a-zA-Z][_a-zA-Z0-9-]*`. Unique within the group. Imports match existing classes by this value within the group |
| `label` | yes | Option text editors see |
| `published` | no | Default `true` |

## Validation

A file is rejected as a whole if any rule above is violated. The error names the
position, e.g. `groups[1].classes[0].class: "1col" is not a valid CSS class name.`
