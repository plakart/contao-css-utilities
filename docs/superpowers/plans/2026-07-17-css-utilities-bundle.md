# plakart/contao-css-utilities Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a Contao 5 bundle that ships 36 fixed spacing utility classes (`mt|mb|pt|pb` × `0…8`) which editors assign via four select fields on content elements, articles, and form fields; selections are merged into the record's class attribute on save, and the CSS loads automatically with a per-layout opt-out.

**Architecture:** A single source of truth (`Util\UtilityClasses`) defines properties, scale, and clamp() values; a generator script writes `public/css/utilities.css` from it. A pure string merger (`Util\UtilityClassMerger`) strips known utility classes and appends the current selection; an `onsubmit` DCA listener applies it to `cssID[1]` (content/article) or `class` (form field). A `generatePage` hook injects the CSS unless the layout disables it.

**Tech Stack:** PHP ≥ 8.2, Contao `^5.0` (contao/core-bundle), Symfony AbstractBundle, Doctrine DBAL, PHPUnit 11, PHPStan, PHP-CS-Fixer.

**Spec:** `docs/superpowers/specs/2026-07-17-css-utilities-bundle-design.md`

## Global Constraints

- Package name: `plakart/contao-css-utilities`, type `contao-bundle`, license `LGPL-3.0-or-later`
- PHP `>=8.2`; `contao/core-bundle ^5.0`
- PSR-4: `Plakart\CssUtilitiesBundle\` → `src/`, tests `Plakart\CssUtilitiesBundle\Tests\` → `tests/`
- English identifiers, comments, commit messages; backend labels in `en` and `de`
- Class names exactly `mt-0…mt-8`, `mb-0…mb-8`, `pt-0…pt-8`, `pb-0…pb-8` (36 total)
- CSS values: step 0 = `0`; steps 1–8 = `var(--space-N)` with `clamp()` defaults; all declarations `!important`
- All commands run from the repo root (`C:\Projekte\contao-utilities-classes-bundle`) in PowerShell; always use the composer scripts (`composer test`, `composer phpstan`, `composer cs-fix`) once defined
- Commit after every task with a conventional-commit message ending in the footer line `Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>`
- Working tree already contains: `CLAUDE.md`, `.gitignore`, `docs/` — do not touch them except where a task says so

---

### Task 1: Package skeleton and QA tooling

**Files:**
- Create: `composer.json`
- Create: `src/PlakartCssUtilitiesBundle.php`
- Create: `src/ContaoManager/Plugin.php`
- Create: `config/services.yaml`
- Create: `phpunit.xml.dist`
- Create: `phpstan.neon.dist`
- Create: `.php-cs-fixer.dist.php`

**Interfaces:**
- Consumes: nothing (first task)
- Produces: installable composer package; namespace `Plakart\CssUtilitiesBundle\`; QA commands `composer test`, `composer phpstan`, `composer cs-fix` used by every later task

- [ ] **Step 1: Verify toolchain**

Run: `php -v` and `composer --version`
Expected: PHP 8.2+ and Composer 2.x. If either is missing, STOP and report — the plan cannot proceed.

- [ ] **Step 2: Create composer.json**

```json
{
    "name": "plakart/contao-css-utilities",
    "description": "Tailwind-style spacing utility classes assignable to Contao content elements, articles and form fields",
    "type": "contao-bundle",
    "license": "LGPL-3.0-or-later",
    "require": {
        "php": ">=8.2",
        "contao/core-bundle": "^5.0",
        "doctrine/dbal": "^3.6 || ^4.0",
        "symfony/config": "^6.4 || ^7.0",
        "symfony/dependency-injection": "^6.4 || ^7.0",
        "symfony/http-kernel": "^6.4 || ^7.0"
    },
    "require-dev": {
        "contao/manager-plugin": "^2.12",
        "friendsofphp/php-cs-fixer": "^3.40",
        "phpstan/phpstan": "^2.0",
        "phpunit/phpunit": "^11.0"
    },
    "conflict": {
        "contao/manager-plugin": "<2.0"
    },
    "autoload": {
        "psr-4": {
            "Plakart\\CssUtilitiesBundle\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Plakart\\CssUtilitiesBundle\\Tests\\": "tests/"
        }
    },
    "extra": {
        "contao-manager-plugin": "Plakart\\CssUtilitiesBundle\\ContaoManager\\Plugin"
    },
    "scripts": {
        "cs-fix": "php-cs-fixer fix",
        "phpstan": "phpstan analyse",
        "test": "phpunit"
    },
    "config": {
        "allow-plugins": {
            "contao-components/installer": true,
            "contao/manager-plugin": true,
            "php-http/discovery": true
        }
    }
}
```

- [ ] **Step 3: Create the bundle class**

`src/PlakartCssUtilitiesBundle.php`:

```php
<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class PlakartCssUtilitiesBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yaml');
    }
}
```

- [ ] **Step 4: Create the Contao Manager plugin**

`src/ContaoManager/Plugin.php`:

```php
<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Plakart\CssUtilitiesBundle\PlakartCssUtilitiesBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(PlakartCssUtilitiesBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
```

- [ ] **Step 5: Create config/services.yaml**

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    Plakart\CssUtilitiesBundle\:
        resource: '../src/'
        exclude:
            - '../src/ContaoManager/'
            - '../src/Util/'
            - '../src/PlakartCssUtilitiesBundle.php'
```

- [ ] **Step 6: Create phpunit.xml.dist**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         failOnWarning="true">
    <testsuites>
        <testsuite name="unit">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

- [ ] **Step 7: Create phpstan.neon.dist**

```neon
parameters:
    level: 6
    paths:
        - src
```

- [ ] **Step 8: Create .php-cs-fixer.dist.php**

```php
<?php

declare(strict_types=1);

$dirs = array_filter(
    [__DIR__.'/src', __DIR__.'/tests', __DIR__.'/contao', __DIR__.'/bin'],
    'is_dir',
);

$finder = PhpCsFixer\Finder::create()->in($dirs);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'declare_strict_types' => true,
    ])
    ->setFinder($finder)
;
```

- [ ] **Step 9: Install dependencies**

Run: `composer install`
Expected: finishes without error, `vendor/` created. (Network required; if a dependency conflict appears, report it verbatim instead of changing constraints silently.)

- [ ] **Step 10: Validate package and run QA baseline**

Run: `composer validate --strict`
Expected: `./composer.json is valid`

Run: `composer phpstan`
Expected: `[OK] No errors`

Run: `composer test`
Expected: PHPUnit runs and reports no tests (that is fine at this point — no test classes exist yet).

- [ ] **Step 11: Commit**

```powershell
git add composer.json src/ config/ phpunit.xml.dist phpstan.neon.dist .php-cs-fixer.dist.php
git commit -m @'
chore: scaffold bundle skeleton and QA tooling

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>
'@
```

Note: `vendor/` and `composer.lock` are gitignored — do NOT force-add them.

---

### Task 2: UtilityClasses — single source of truth

**Files:**
- Create: `src/Util/UtilityClasses.php`
- Test: `tests/Util/UtilityClassesTest.php`

**Interfaces:**
- Consumes: nothing
- Produces (used by Tasks 3–5):
  - `UtilityClasses::PROPERTIES` — `array<string, string>` prefix → CSS property, exactly `['mt' => 'margin-top', 'mb' => 'margin-bottom', 'pt' => 'padding-top', 'pb' => 'padding-bottom']`
  - `UtilityClasses::SCALE` — `array<int, string>` step (1–8) → clamp() expression
  - `UtilityClasses::steps(): array` — `[0, 1, …, 8]`
  - `UtilityClasses::all(): array` — all 36 class names, e.g. `mt-0`, `pb-8`
  - `UtilityClasses::generateCss(): string` — full stylesheet content

- [ ] **Step 1: Write the failing test**

`tests/Util/UtilityClassesTest.php`:

```php
<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\Util\UtilityClasses;

final class UtilityClassesTest extends TestCase
{
    public function testDefinesFourPropertiesAndNineSteps(): void
    {
        self::assertSame(['mt', 'mb', 'pt', 'pb'], array_keys(UtilityClasses::PROPERTIES));
        self::assertSame(range(0, 8), UtilityClasses::steps());
        self::assertSame(range(1, 8), array_keys(UtilityClasses::SCALE));
    }

    public function testAllContainsExactly36UniqueClasses(): void
    {
        $all = UtilityClasses::all();

        self::assertCount(36, $all);
        self::assertSame($all, array_unique($all));
        self::assertContains('mt-0', $all);
        self::assertContains('mb-3', $all);
        self::assertContains('pt-5', $all);
        self::assertContains('pb-8', $all);
    }

    public function testGeneratedCssContainsCustomPropertiesAndRules(): void
    {
        $css = UtilityClasses::generateCss();

        self::assertStringContainsString('--space-4: clamp(1rem, 0.8rem + 1vw, 2rem);', $css);
        self::assertStringContainsString('.mt-0 { margin-top: 0 !important; }', $css);
        self::assertStringContainsString('.pb-8 { padding-bottom: var(--space-8) !important; }', $css);
        self::assertSame(36, preg_match_all('/^\.[a-z]{2}-\d \{ [a-z-]+: [^;]+ !important; \}$/m', $css));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test`
Expected: FAIL — `Class "Plakart\CssUtilitiesBundle\Util\UtilityClasses" not found`

- [ ] **Step 3: Write the implementation**

`src/Util/UtilityClasses.php`:

```php
<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Util;

final class UtilityClasses
{
    public const PROPERTIES = [
        'mt' => 'margin-top',
        'mb' => 'margin-bottom',
        'pt' => 'padding-top',
        'pb' => 'padding-bottom',
    ];

    public const SCALE = [
        1 => 'clamp(0.25rem, 0.2rem + 0.25vw, 0.5rem)',
        2 => 'clamp(0.5rem, 0.4rem + 0.5vw, 1rem)',
        3 => 'clamp(0.75rem, 0.6rem + 0.75vw, 1.5rem)',
        4 => 'clamp(1rem, 0.8rem + 1vw, 2rem)',
        5 => 'clamp(1.5rem, 1.2rem + 1.5vw, 3rem)',
        6 => 'clamp(2rem, 1.6rem + 2vw, 4rem)',
        7 => 'clamp(3rem, 2.4rem + 3vw, 6rem)',
        8 => 'clamp(4rem, 3.2rem + 4vw, 8rem)',
    ];

    /**
     * @return list<int>
     */
    public static function steps(): array
    {
        return range(0, 8);
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $classes = [];

        foreach (array_keys(self::PROPERTIES) as $prefix) {
            foreach (self::steps() as $step) {
                $classes[] = $prefix.'-'.$step;
            }
        }

        return $classes;
    }

    public static function generateCss(): string
    {
        $lines = [':root {'];

        foreach (self::SCALE as $step => $value) {
            $lines[] = sprintf('  --space-%d: %s;', $step, $value);
        }

        $lines[] = '}';
        $lines[] = '';

        foreach (self::PROPERTIES as $prefix => $property) {
            foreach (self::steps() as $step) {
                $value = 0 === $step ? '0' : sprintf('var(--space-%d)', $step);
                $lines[] = sprintf('.%s-%d { %s: %s !important; }', $prefix, $step, $property, $value);
            }
        }

        return implode("\n", $lines)."\n";
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test`
Expected: `OK (3 tests, ...)`

- [ ] **Step 5: QA and commit**

Run: `composer cs-fix` then `composer phpstan` then `composer test`
Expected: no remaining diffs, `[OK] No errors`, tests green.

```powershell
git add src/Util/UtilityClasses.php tests/Util/UtilityClassesTest.php
git commit -m @'
feat: add utility class definitions

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>
'@
```

---

### Task 3: CSS generator script and committed stylesheet

**Files:**
- Create: `bin/generate-css.php`
- Create: `public/css/utilities.css` (generated — never edit by hand)
- Modify: `composer.json` (add `generate-css` script)
- Test: `tests/Util/GeneratedCssTest.php`

**Interfaces:**
- Consumes: `UtilityClasses::generateCss(): string` (Task 2)
- Produces: `public/css/utilities.css` (published as `bundles/plakartcssutilities/css/utilities.css`, consumed by Task 6); `composer generate-css` command

- [ ] **Step 1: Write the failing test**

`tests/Util/GeneratedCssTest.php`:

```php
<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\Util\UtilityClasses;

final class GeneratedCssTest extends TestCase
{
    public function testCommittedCssMatchesGeneratorOutput(): void
    {
        $file = \dirname(__DIR__, 2).'/public/css/utilities.css';

        self::assertFileExists($file, 'Run "composer generate-css" and commit the result.');
        self::assertStringEqualsFile($file, UtilityClasses::generateCss());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test`
Expected: FAIL — `Failed asserting that file ".../public/css/utilities.css" exists`

- [ ] **Step 3: Write the generator script**

`bin/generate-css.php`:

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);

use Plakart\CssUtilitiesBundle\Util\UtilityClasses;

require __DIR__.'/../vendor/autoload.php';

$target = __DIR__.'/../public/css/utilities.css';

if (!is_dir(\dirname($target))) {
    mkdir(\dirname($target), 0775, true);
}

file_put_contents($target, UtilityClasses::generateCss());

echo 'Generated '.$target."\n";
```

Add the script to `composer.json` — in the existing `"scripts"` block, add one line:

```json
        "generate-css": "php bin/generate-css.php"
```

- [ ] **Step 4: Generate the CSS and verify the test passes**

Run: `composer generate-css`
Expected: `Generated ...public/css/utilities.css`

Run: `composer test`
Expected: all tests PASS (Task 2 tests + this one).

- [ ] **Step 5: QA and commit**

Run: `composer cs-fix`, `composer phpstan`, `composer validate --strict`
Expected: all green.

```powershell
git add bin/ public/ composer.json tests/Util/GeneratedCssTest.php
git commit -m @'
feat: generate utilities.css from class definitions

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>
'@
```

---

### Task 4: UtilityClassMerger — pure merge/strip logic

**Files:**
- Create: `src/Util/UtilityClassMerger.php`
- Test: `tests/Util/UtilityClassMergerTest.php`

**Interfaces:**
- Consumes: `UtilityClasses::all(): array` (Task 2)
- Produces (used by Task 5):
  - `UtilityClassMerger::merge(string $existing, array $selection): string` — `$selection` is `array<string, string>` mapping prefix (`mt`, `mb`, `pt`, `pb`) to a step string `'0'…'8'` or `''` (= no class). Returns the rebuilt class string: foreign classes first (original order), then selected utility classes in `$selection` order, single-space separated, no leading/trailing whitespace.

- [ ] **Step 1: Write the failing test**

`tests/Util/UtilityClassMergerTest.php`:

```php
<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\Util\UtilityClassMerger;

final class UtilityClassMergerTest extends TestCase
{
    public function testAddsSelectedClassesToEmptyString(): void
    {
        self::assertSame(
            'mt-4 pb-2',
            UtilityClassMerger::merge('', ['mt' => '4', 'mb' => '', 'pt' => '', 'pb' => '2']),
        );
    }

    public function testStepZeroIsAValidSelection(): void
    {
        self::assertSame(
            'mt-0',
            UtilityClassMerger::merge('', ['mt' => '0', 'mb' => '', 'pt' => '', 'pb' => '']),
        );
    }

    public function testReplacesPreviousUtilityClassesAndKeepsForeignOnes(): void
    {
        self::assertSame(
            'block headline mt-4',
            UtilityClassMerger::merge('block mt-2 headline', ['mt' => '4', 'mb' => '', 'pt' => '', 'pb' => '']),
        );
    }

    public function testIsIdempotent(): void
    {
        $selection = ['mt' => '1', 'mb' => '8', 'pt' => '', 'pb' => ''];
        $once = UtilityClassMerger::merge('foo', $selection);

        self::assertSame($once, UtilityClassMerger::merge($once, $selection));
    }

    public function testEmptySelectionRemovesAllUtilityClasses(): void
    {
        self::assertSame(
            'foo',
            UtilityClassMerger::merge('mt-3 foo pb-0', ['mt' => '', 'mb' => '', 'pt' => '', 'pb' => '']),
        );
    }

    public function testUnknownLookalikeClassesArePreserved(): void
    {
        self::assertSame(
            'mt-99 mx-4 mt-2',
            UtilityClassMerger::merge('mt-99 mx-4', ['mt' => '2', 'mb' => '', 'pt' => '', 'pb' => '']),
        );
    }

    public function testNormalizesWhitespace(): void
    {
        self::assertSame(
            'a b',
            UtilityClassMerger::merge("  a \t b  ", ['mt' => '', 'mb' => '', 'pt' => '', 'pb' => '']),
        );
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test`
Expected: FAIL — `Class "Plakart\CssUtilitiesBundle\Util\UtilityClassMerger" not found`

- [ ] **Step 3: Write the implementation**

`src/Util/UtilityClassMerger.php`:

```php
<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Util;

final class UtilityClassMerger
{
    /**
     * Strips all known utility classes from $existing and appends the current selection.
     *
     * @param array<string, string> $selection prefix => step ('' = no class)
     */
    public static function merge(string $existing, array $selection): string
    {
        $classes = preg_split('/\s+/', trim($existing), -1, \PREG_SPLIT_NO_EMPTY) ?: [];
        $classes = array_values(array_diff($classes, UtilityClasses::all()));

        foreach ($selection as $prefix => $step) {
            if ('' === trim($step)) {
                continue;
            }

            $classes[] = $prefix.'-'.$step;
        }

        return implode(' ', $classes);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test`
Expected: all tests PASS.

- [ ] **Step 5: QA and commit**

Run: `composer cs-fix`, `composer phpstan`
Expected: green.

```powershell
git add src/Util/UtilityClassMerger.php tests/Util/UtilityClassMergerTest.php
git commit -m @'
feat: add utility class merger

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>
'@
```

---

### Task 5: DCA fields, save listener, and labels for tl_content / tl_article / tl_form_field

**Files:**
- Create: `src/Util/UtilityFields.php`
- Create: `src/EventListener/DataContainer/UtilityClassSaveListener.php`
- Create: `contao/dca/tl_content.php`, `contao/dca/tl_article.php`, `contao/dca/tl_form_field.php`
- Create: `contao/languages/en/tl_content.php`, `contao/languages/en/tl_article.php`, `contao/languages/en/tl_form_field.php`
- Create: `contao/languages/de/tl_content.php`, `contao/languages/de/tl_article.php`, `contao/languages/de/tl_form_field.php`
- Test: `tests/Util/UtilityFieldsTest.php`, `tests/EventListener/DataContainer/UtilityClassSaveListenerTest.php`

**Interfaces:**
- Consumes: `UtilityClasses::steps()` (Task 2), `UtilityClassMerger::merge(string, array): string` (Task 4)
- Produces:
  - DB columns `utilityMt`, `utilityMb`, `utilityPt`, `utilityPb` (varchar(2), default `''`) on all three tables
  - `UtilityFields::register(string $table): void` — adds the four fields + `utility_legend` to every palette of `$table`
  - `UtilityClassSaveListener::updateCssId(DataContainer $dc): void` (tl_content, tl_article) and `::updateClass(DataContainer $dc): void` (tl_form_field), registered via `#[AsCallback(..., 'config.onsubmit')]`

- [ ] **Step 1: Write the failing UtilityFields test**

`tests/Util/UtilityFieldsTest.php`:

```php
<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\Util\UtilityFields;

final class UtilityFieldsTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testRegistersFieldsAndAppendsLegendToAllPalettes(): void
    {
        $GLOBALS['TL_DCA']['tl_content'] = [
            'palettes' => [
                '__selector__' => ['addImage'],
                'text' => '{type_legend},type;{expert_legend:hide},cssID',
                'image' => '{type_legend},type;{expert_legend:hide},cssID',
            ],
            'fields' => [],
        ];

        UtilityFields::register('tl_content');

        foreach (['utilityMt', 'utilityMb', 'utilityPt', 'utilityPb'] as $field) {
            self::assertArrayHasKey($field, $GLOBALS['TL_DCA']['tl_content']['fields']);
            self::assertSame('select', $GLOBALS['TL_DCA']['tl_content']['fields'][$field]['inputType']);
            self::assertSame(
                ['0', '1', '2', '3', '4', '5', '6', '7', '8'],
                $GLOBALS['TL_DCA']['tl_content']['fields'][$field]['options'],
            );
        }

        foreach (['text', 'image'] as $palette) {
            self::assertStringContainsString('utility_legend', $GLOBALS['TL_DCA']['tl_content']['palettes'][$palette]);
            self::assertStringContainsString('utilityMt,utilityMb,utilityPt,utilityPb', $GLOBALS['TL_DCA']['tl_content']['palettes'][$palette]);
        }

        self::assertSame(['addImage'], $GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test`
Expected: FAIL — `Class "Plakart\CssUtilitiesBundle\Util\UtilityFields" not found`

- [ ] **Step 3: Implement UtilityFields**

`src/Util/UtilityFields.php`:

```php
<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Util;

use Contao\CoreBundle\DataContainer\PaletteManipulator;

final class UtilityFields
{
    public const FIELDS = ['utilityMt', 'utilityMb', 'utilityPt', 'utilityPb'];

    /**
     * Adds the four utility select fields and the utility_legend to every palette of the table.
     */
    public static function register(string $table): void
    {
        $options = array_map(strval(...), UtilityClasses::steps());

        foreach (self::FIELDS as $field) {
            $GLOBALS['TL_DCA'][$table]['fields'][$field] = [
                'exclude' => true,
                'inputType' => 'select',
                'options' => $options,
                'eval' => ['includeBlankOption' => true, 'tl_class' => 'w25'],
                'sql' => ['type' => 'string', 'length' => 2, 'default' => ''],
            ];
        }

        $manipulator = PaletteManipulator::create()
            ->addLegend('utility_legend', 'expert_legend', PaletteManipulator::POSITION_BEFORE, true)
            ->addField(self::FIELDS, 'utility_legend', PaletteManipulator::POSITION_APPEND)
        ;

        foreach ($GLOBALS['TL_DCA'][$table]['palettes'] ?? [] as $name => $palette) {
            if (!\is_string($palette)) {
                continue;
            }

            $manipulator->applyToPalette($name, $table);
        }
    }
}
```

- [ ] **Step 4: Run UtilityFields test to verify it passes**

Run: `composer test`
Expected: PASS.

- [ ] **Step 5: Write the failing save-listener test**

`tests/EventListener/DataContainer/UtilityClassSaveListenerTest.php`:

```php
<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\EventListener\DataContainer;

use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\EventListener\DataContainer\UtilityClassSaveListener;

final class UtilityClassSaveListenerTest extends TestCase
{
    public function testMergesSelectionIntoCssIdOfContentElement(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAssociative')
            ->willReturn([
                'cssID' => serialize(['hero', 'block mt-2']),
                'utilityMt' => '4',
                'utilityMb' => '',
                'utilityPt' => '0',
                'utilityPb' => '',
            ])
        ;
        $connection
            ->expects(self::once())
            ->method('update')
            ->with(
                'tl_content',
                ['cssID' => serialize(['hero', 'block mt-4 pt-0'])],
                ['id' => 5],
            )
        ;

        $dc = $this->createMock(DataContainer::class);
        $dc->method('__get')->willReturnMap([
            ['table', 'tl_content'],
            ['id', 5],
        ]);

        (new UtilityClassSaveListener($connection))->updateCssId($dc);
    }

    public function testHandlesEmptyCssIdColumn(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAssociative')
            ->willReturn([
                'cssID' => null,
                'utilityMt' => '',
                'utilityMb' => '2',
                'utilityPt' => '',
                'utilityPb' => '',
            ])
        ;
        $connection
            ->expects(self::once())
            ->method('update')
            ->with(
                'tl_article',
                ['cssID' => serialize(['', 'mb-2'])],
                ['id' => 7],
            )
        ;

        $dc = $this->createMock(DataContainer::class);
        $dc->method('__get')->willReturnMap([
            ['table', 'tl_article'],
            ['id', 7],
        ]);

        (new UtilityClassSaveListener($connection))->updateCssId($dc);
    }

    public function testMergesSelectionIntoFormFieldClass(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAssociative')
            ->willReturn([
                'class' => 'widget pb-1',
                'utilityMt' => '',
                'utilityMb' => '',
                'utilityPt' => '',
                'utilityPb' => '3',
            ])
        ;
        $connection
            ->expects(self::once())
            ->method('update')
            ->with(
                'tl_form_field',
                ['class' => 'widget pb-3'],
                ['id' => 9],
            )
        ;

        $dc = $this->createMock(DataContainer::class);
        $dc->method('__get')->willReturnMap([
            ['table', 'tl_form_field'],
            ['id', 9],
        ]);

        (new UtilityClassSaveListener($connection))->updateClass($dc);
    }

    public function testDoesNothingWhenRecordIsMissing(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAssociative')->willReturn(false);
        $connection->expects(self::never())->method('update');

        $dc = $this->createMock(DataContainer::class);
        $dc->method('__get')->willReturnMap([
            ['table', 'tl_content'],
            ['id', 1],
        ]);

        (new UtilityClassSaveListener($connection))->updateCssId($dc);
    }
}
```

- [ ] **Step 6: Run test to verify it fails**

Run: `composer test`
Expected: FAIL — `Class "...UtilityClassSaveListener" not found`

- [ ] **Step 7: Implement the save listener**

`src/EventListener/DataContainer/UtilityClassSaveListener.php`:

```php
<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Plakart\CssUtilitiesBundle\Util\UtilityClassMerger;

class UtilityClassSaveListener
{
    public function __construct(private readonly Connection $connection)
    {
    }

    #[AsCallback('tl_content', 'config.onsubmit')]
    #[AsCallback('tl_article', 'config.onsubmit')]
    public function updateCssId(DataContainer $dc): void
    {
        $row = $this->fetchRow($dc);

        if (null === $row) {
            return;
        }

        $cssId = StringUtil::deserialize($row['cssID'] ?? null, true) + ['', ''];
        $cssId[1] = UtilityClassMerger::merge((string) $cssId[1], $this->getSelection($row));

        $this->connection->update(
            $dc->table,
            ['cssID' => serialize([(string) $cssId[0], $cssId[1]])],
            ['id' => (int) $dc->id],
        );
    }

    #[AsCallback('tl_form_field', 'config.onsubmit')]
    public function updateClass(DataContainer $dc): void
    {
        $row = $this->fetchRow($dc);

        if (null === $row) {
            return;
        }

        $class = UtilityClassMerger::merge((string) ($row['class'] ?? ''), $this->getSelection($row));

        $this->connection->update('tl_form_field', ['class' => $class], ['id' => (int) $dc->id]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchRow(DataContainer $dc): ?array
    {
        $row = $this->connection->fetchAssociative(
            sprintf('SELECT * FROM %s WHERE id = ?', $dc->table),
            [(int) $dc->id],
        );

        return false === $row ? null : $row;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, string>
     */
    private function getSelection(array $row): array
    {
        return [
            'mt' => (string) ($row['utilityMt'] ?? ''),
            'mb' => (string) ($row['utilityMb'] ?? ''),
            'pt' => (string) ($row['utilityPt'] ?? ''),
            'pb' => (string) ($row['utilityPb'] ?? ''),
        ];
    }
}
```

- [ ] **Step 8: Run test to verify it passes**

Run: `composer test`
Expected: all tests PASS.

- [ ] **Step 9: Create the DCA files**

`contao/dca/tl_content.php`:

```php
<?php

declare(strict_types=1);

use Plakart\CssUtilitiesBundle\Util\UtilityFields;

UtilityFields::register('tl_content');
```

`contao/dca/tl_article.php`:

```php
<?php

declare(strict_types=1);

use Plakart\CssUtilitiesBundle\Util\UtilityFields;

UtilityFields::register('tl_article');
```

`contao/dca/tl_form_field.php`:

```php
<?php

declare(strict_types=1);

use Plakart\CssUtilitiesBundle\Util\UtilityFields;

UtilityFields::register('tl_form_field');
```

- [ ] **Step 10: Create the language files**

`contao/languages/en/tl_content.php`:

```php
<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_content']['utility_legend'] = 'Spacing utilities';
$GLOBALS['TL_LANG']['tl_content']['utilityMt'] = ['Margin top', 'Adds the corresponding mt-* class (scale 0-8).'];
$GLOBALS['TL_LANG']['tl_content']['utilityMb'] = ['Margin bottom', 'Adds the corresponding mb-* class (scale 0-8).'];
$GLOBALS['TL_LANG']['tl_content']['utilityPt'] = ['Padding top', 'Adds the corresponding pt-* class (scale 0-8).'];
$GLOBALS['TL_LANG']['tl_content']['utilityPb'] = ['Padding bottom', 'Adds the corresponding pb-* class (scale 0-8).'];
```

`contao/languages/en/tl_article.php`:

```php
<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_article']['utility_legend'] = 'Spacing utilities';
$GLOBALS['TL_LANG']['tl_article']['utilityMt'] = ['Margin top', 'Adds the corresponding mt-* class (scale 0-8).'];
$GLOBALS['TL_LANG']['tl_article']['utilityMb'] = ['Margin bottom', 'Adds the corresponding mb-* class (scale 0-8).'];
$GLOBALS['TL_LANG']['tl_article']['utilityPt'] = ['Padding top', 'Adds the corresponding pt-* class (scale 0-8).'];
$GLOBALS['TL_LANG']['tl_article']['utilityPb'] = ['Padding bottom', 'Adds the corresponding pb-* class (scale 0-8).'];
```

`contao/languages/en/tl_form_field.php`:

```php
<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_form_field']['utility_legend'] = 'Spacing utilities';
$GLOBALS['TL_LANG']['tl_form_field']['utilityMt'] = ['Margin top', 'Adds the corresponding mt-* class (scale 0-8).'];
$GLOBALS['TL_LANG']['tl_form_field']['utilityMb'] = ['Margin bottom', 'Adds the corresponding mb-* class (scale 0-8).'];
$GLOBALS['TL_LANG']['tl_form_field']['utilityPt'] = ['Padding top', 'Adds the corresponding pt-* class (scale 0-8).'];
$GLOBALS['TL_LANG']['tl_form_field']['utilityPb'] = ['Padding bottom', 'Adds the corresponding pb-* class (scale 0-8).'];
```

`contao/languages/de/tl_content.php`:

```php
<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_content']['utility_legend'] = 'Abstände (Utilities)';
$GLOBALS['TL_LANG']['tl_content']['utilityMt'] = ['Außenabstand oben', 'Fügt die entsprechende mt-*-Klasse hinzu (Skala 0-8).'];
$GLOBALS['TL_LANG']['tl_content']['utilityMb'] = ['Außenabstand unten', 'Fügt die entsprechende mb-*-Klasse hinzu (Skala 0-8).'];
$GLOBALS['TL_LANG']['tl_content']['utilityPt'] = ['Innenabstand oben', 'Fügt die entsprechende pt-*-Klasse hinzu (Skala 0-8).'];
$GLOBALS['TL_LANG']['tl_content']['utilityPb'] = ['Innenabstand unten', 'Fügt die entsprechende pb-*-Klasse hinzu (Skala 0-8).'];
```

`contao/languages/de/tl_article.php`:

```php
<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_article']['utility_legend'] = 'Abstände (Utilities)';
$GLOBALS['TL_LANG']['tl_article']['utilityMt'] = ['Außenabstand oben', 'Fügt die entsprechende mt-*-Klasse hinzu (Skala 0-8).'];
$GLOBALS['TL_LANG']['tl_article']['utilityMb'] = ['Außenabstand unten', 'Fügt die entsprechende mb-*-Klasse hinzu (Skala 0-8).'];
$GLOBALS['TL_LANG']['tl_article']['utilityPt'] = ['Innenabstand oben', 'Fügt die entsprechende pt-*-Klasse hinzu (Skala 0-8).'];
$GLOBALS['TL_LANG']['tl_article']['utilityPb'] = ['Innenabstand unten', 'Fügt die entsprechende pb-*-Klasse hinzu (Skala 0-8).'];
```

`contao/languages/de/tl_form_field.php`:

```php
<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_form_field']['utility_legend'] = 'Abstände (Utilities)';
$GLOBALS['TL_LANG']['tl_form_field']['utilityMt'] = ['Außenabstand oben', 'Fügt die entsprechende mt-*-Klasse hinzu (Skala 0-8).'];
$GLOBALS['TL_LANG']['tl_form_field']['utilityMb'] = ['Außenabstand unten', 'Fügt die entsprechende mb-*-Klasse hinzu (Skala 0-8).'];
$GLOBALS['TL_LANG']['tl_form_field']['utilityPt'] = ['Innenabstand oben', 'Fügt die entsprechende pt-*-Klasse hinzu (Skala 0-8).'];
$GLOBALS['TL_LANG']['tl_form_field']['utilityPb'] = ['Innenabstand unten', 'Fügt die entsprechende pb-*-Klasse hinzu (Skala 0-8).'];
```

(These files are framework-loaded; there is no unit test for them. The array key MUST match the file's table name.)

- [ ] **Step 11: QA and commit**

Run: `composer cs-fix`, `composer phpstan`, `composer test`
Expected: all green.

```powershell
git add src/Util/UtilityFields.php src/EventListener/ contao/ tests/
git commit -m @'
feat: add DCA fields and save listener for content, article and form field

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>
'@
```

---

### Task 6: generatePage hook and layout opt-out

**Files:**
- Create: `src/EventListener/GeneratePageListener.php`
- Create: `contao/dca/tl_layout.php`
- Create: `contao/languages/en/tl_layout.php`, `contao/languages/de/tl_layout.php`
- Test: `tests/EventListener/GeneratePageListenerTest.php`

**Interfaces:**
- Consumes: `public/css/utilities.css` (Task 3; published under `bundles/plakartcssutilities/`)
- Produces: `tl_layout.disableUtilityCss` checkbox column (boolean, default false); CSS injected via `$GLOBALS['TL_CSS']`

- [ ] **Step 1: Write the failing test**

`tests/EventListener/GeneratePageListenerTest.php`:

```php
<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\EventListener;

use Contao\LayoutModel;
use Contao\PageModel;
use Contao\PageRegular;
use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\EventListener\GeneratePageListener;

final class GeneratePageListenerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        unset($GLOBALS['TL_CSS']);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_CSS']);

        parent::tearDown();
    }

    public function testAddsStylesheetByDefault(): void
    {
        $this->invokeListener('');

        self::assertSame(['bundles/plakartcssutilities/css/utilities.css|static'], $GLOBALS['TL_CSS']);
    }

    public function testSkipsStylesheetWhenLayoutDisablesIt(): void
    {
        $this->invokeListener('1');

        self::assertArrayNotHasKey('TL_CSS', $GLOBALS);
    }

    private function invokeListener(string $disableUtilityCss): void
    {
        $layout = $this->createMock(LayoutModel::class);
        $layout->method('__get')->with('disableUtilityCss')->willReturn($disableUtilityCss);

        (new GeneratePageListener())(
            $this->createMock(PageModel::class),
            $layout,
            $this->createMock(PageRegular::class),
        );
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test`
Expected: FAIL — `Class "...GeneratePageListener" not found`

- [ ] **Step 3: Implement the listener**

`src/EventListener/GeneratePageListener.php`:

```php
<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\PageRegular;

#[AsHook('generatePage')]
class GeneratePageListener
{
    public function __invoke(PageModel $pageModel, LayoutModel $layout, PageRegular $pageRegular): void
    {
        if ($layout->disableUtilityCss) {
            return;
        }

        $GLOBALS['TL_CSS'][] = 'bundles/plakartcssutilities/css/utilities.css|static';
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test`
Expected: all tests PASS.

- [ ] **Step 5: Create tl_layout DCA and language files**

`contao/dca/tl_layout.php`:

```php
<?php

declare(strict_types=1);

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_layout']['fields']['disableUtilityCss'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50'],
    'sql' => ['type' => 'boolean', 'default' => false],
];

PaletteManipulator::create()
    ->addLegend('utility_legend', 'style_legend', PaletteManipulator::POSITION_AFTER, true)
    ->addField('disableUtilityCss', 'utility_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_layout');
```

`contao/languages/en/tl_layout.php`:

```php
<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_layout']['utility_legend'] = 'Spacing utilities';
$GLOBALS['TL_LANG']['tl_layout']['disableUtilityCss'] = ['Disable utility CSS', 'Do not load the utilities.css of the CSS utilities bundle in this layout.'];
```

`contao/languages/de/tl_layout.php`:

```php
<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_layout']['utility_legend'] = 'Abstände (Utilities)';
$GLOBALS['TL_LANG']['tl_layout']['disableUtilityCss'] = ['Utility-CSS deaktivieren', 'Die utilities.css des CSS-Utilities-Bundles in diesem Layout nicht laden.'];
```

- [ ] **Step 6: QA and commit**

Run: `composer cs-fix`, `composer phpstan`, `composer test`
Expected: all green.

```powershell
git add src/EventListener/GeneratePageListener.php contao/dca/tl_layout.php contao/languages/ tests/EventListener/GeneratePageListenerTest.php
git commit -m @'
feat: load utilities.css via generatePage hook with layout opt-out

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>
'@
```

---

### Task 7: README and final QA sweep

**Files:**
- Create: `README.md`

**Interfaces:**
- Consumes: everything above
- Produces: user-facing documentation; verified-green package

- [ ] **Step 1: Write README.md**

```markdown
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
```

- [ ] **Step 2: Full QA sweep**

Run, in order: `composer validate --strict`, `composer cs-fix`, `composer phpstan`, `composer test`, `composer generate-css`
Expected: everything green; `git status` shows ONLY `README.md` as new — if `generate-css` changed `public/css/utilities.css`, the definitions and committed CSS diverged somewhere: investigate before committing.

- [ ] **Step 3: Commit**

```powershell
git add README.md
git commit -m @'
docs: add README

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>
'@
```

---

## Out of scope (deliberate)

- No responsive breakpoint variants (spec decision)
- No project-level configuration of the class set (fixed by design)
- No live verification inside a running Contao app — that requires a Contao installation and is a follow-up once the bundle is required in a real project via a path repository
