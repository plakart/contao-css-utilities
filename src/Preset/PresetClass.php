<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Preset;

final readonly class PresetClass
{
    public function __construct(
        public string $class,
        public string $label,
        public bool $published = true,
    ) {
    }
}
