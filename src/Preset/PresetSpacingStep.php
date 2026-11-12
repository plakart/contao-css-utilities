<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Preset;

final readonly class PresetSpacingStep
{
    public function __construct(
        public int $step,
        public string $label = '',
    ) {
    }
}
