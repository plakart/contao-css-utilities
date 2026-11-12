<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\ClassSelection;

/**
 * A published class group with its published classes.
 */
final readonly class ClassGroup
{
    /**
     * @param array<string, list<string>> $targets target key => allowed types ([] = all types)
     * @param array<string, string>       $classes CSS class => label, in sorting order
     */
    public function __construct(
        public int $id,
        public string $alias,
        public string $title,
        public bool $multiple,
        public array $targets,
        public array $classes,
    ) {
    }

    public function appliesTo(string $targetKey, ?string $type): bool
    {
        if (!isset($this->targets[$targetKey])) {
            return false;
        }

        $types = $this->targets[$targetKey];

        return [] === $types || (null !== $type && \in_array($type, $types, true));
    }
}
