<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Preset;

final readonly class PresetGroup
{
    /**
     * @param array<string, list<string>> $targets target key => types ([] = all types)
     * @param list<PresetClass>           $classes
     */
    public function __construct(
        public string $alias,
        public string $title,
        public bool $multiple = false,
        public array $targets = [],
        public array $classes = [],
        public bool $published = true,
    ) {
    }
}
