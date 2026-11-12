<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Target;

/**
 * A table whose records can receive CSS classes from the class selection.
 */
final readonly class Target
{
    public function __construct(
        public string $key,
        public string $table,
        public string $column,
        public ?string $typeField = null,
        public ?string $requiredBundle = null,
    ) {
    }

    /**
     * Whether the class column is the serialized [id, class] pair of a cssID field.
     */
    public function isCssId(): bool
    {
        return 'cssID' === $this->column;
    }
}
