<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\EventListener\DataContainer;

use Plakart\CssUtilitiesBundle\Spacing\SpacingStepRepository;
use Plakart\CssUtilitiesBundle\Util\UtilityClasses;

/**
 * Options of the four spacing selects: the fixed steps 0-12 plus the additional steps.
 *
 * Registered as options_callback in UtilityFields::register() (service is public).
 */
class SpacingOptionsListener
{
    public function __construct(private readonly SpacingStepRepository $steps)
    {
    }

    /**
     * @return array<int, string> step => label (PHP turns the numeric keys into integers)
     */
    public function __invoke(): array
    {
        $options = [];

        foreach (UtilityClasses::steps() as $step) {
            $options[$step] = (string) $step;
        }

        foreach ($this->steps->findAll() as $step => $label) {
            $options[$step] = '' === $label ? (string) $step : \sprintf('%d – %s', $step, $label);
        }

        return $options;
    }
}
