<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\EventListener\DataContainer;

use Contao\CoreBundle\DataContainer\RecordLabel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\StringUtil;
use Contao\System;
use Plakart\CssUtilitiesBundle\EventListener\LoadDataContainerListener;
use Plakart\CssUtilitiesBundle\Util\UtilityClasses;

/**
 * Shows the assigned spacing utilities and selected CSS classes in the element card header.
 *
 * Wraps the existing list.label.label_callback (registered by the Contao core or
 * a third-party bundle) in a config.onload callback and appends the selected
 * utility classes to the header label.
 */
class UtilityClassLabelListener
{
    private ?\Closure $wrapper = null;

    #[AsCallback('tl_content', 'config.onload')]
    public function __invoke(?DataContainer $dc = null): void
    {
        $existing = $GLOBALS['TL_DCA']['tl_content']['list']['label']['label_callback'] ?? null;

        // Already wrapped (onload callbacks may run more than once per request)
        if (null !== $this->wrapper && $existing === $this->wrapper) {
            return;
        }

        $this->wrapper = function (array $row, string $label = '', mixed ...$arguments) use ($existing): array|object|string {
            $result = $this->invokeExisting($existing, $row, $label, $arguments) ?? $label;

            return self::appendClasses($result, $row);
        };

        $GLOBALS['TL_DCA']['tl_content']['list']['label']['label_callback'] = $this->wrapper;
    }

    /**
     * Appends the utility classes of the record to the header label. The label is the
     * plain label string, [label, preview, state] in the card view, or a RecordLabel
     * object (Contao 6).
     *
     * @template T of array<int, string>|object|string
     *
     * @param T                    $label
     * @param array<string, mixed> $row
     *
     * @return T
     */
    public static function appendClasses(array|object|string $label, array $row): array|object|string
    {
        $classes = [];

        foreach (array_keys(UtilityClasses::PROPERTIES) as $prefix) {
            $step = trim((string) ($row['utility'.ucfirst($prefix)] ?? ''));

            if ('' !== $step) {
                $classes[] = $prefix.'-'.$step;
            }
        }

        // The stored selection is normalized on save, so it can be shown as is
        $selection = StringUtil::deserialize($row[LoadDataContainerListener::FIELD] ?? null);

        foreach (\is_array($selection) ? $selection : [] as $groupClasses) {
            foreach ((array) $groupClasses as $class) {
                if (\is_string($class) && '' !== $class) {
                    $classes[] = $class;
                }
            }
        }

        if ([] === $classes) {
            return $label;
        }

        $badge = ' <span class="tl_gray pcu-utilities">'.htmlspecialchars(implode(' ', $classes)).'</span>';

        // Contao 6: Contao\CoreBundle\DataContainer\RecordLabel (class does not exist in 5.7)
        if ($label instanceof RecordLabel) {
            $label->htmlLabel = ($label->htmlLabel ?? StringUtil::specialchars($label->label)).$badge;

            return $label;
        }

        if (\is_object($label)) {
            return $label;
        }

        if (\is_array($label)) {
            $label[0] = ($label[0] ?? '').$badge;

            return $label;
        }

        return $label.$badge;
    }

    /**
     * Invokes the wrapped callback the same way DC_Table would (array notation
     * via the service locator, everything else directly).
     *
     * @param array<string, mixed> $row
     * @param list<mixed>          $arguments
     *
     * @return array<int, string>|object|string|null
     */
    private function invokeExisting(mixed $existing, array $row, string $label, array $arguments): array|object|string|null
    {
        if (\is_array($existing)) {
            return System::importStatic($existing[0])->{$existing[1]}($row, $label, ...$arguments);
        }

        if (\is_callable($existing)) {
            return $existing($row, $label, ...$arguments);
        }

        return null;
    }
}
