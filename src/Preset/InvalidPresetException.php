<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Preset;

final class InvalidPresetException extends \RuntimeException
{
    public static function at(string $path, string $message): self
    {
        return new self('' === $path ? $message : \sprintf('%s: %s', $path, $message));
    }
}
