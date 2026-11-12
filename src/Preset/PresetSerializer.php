<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Preset;

use Plakart\CssUtilitiesBundle\EventListener\DataContainer\ClassGroupListener;
use Plakart\CssUtilitiesBundle\EventListener\DataContainer\ClassListener;
use Plakart\CssUtilitiesBundle\Util\ClassAttributeMerger;

/**
 * Reads and writes the JSON preset format (see docs/preset-format.md).
 */
final class PresetSerializer
{
    /**
     * @throws InvalidPresetException
     */
    public static function fromJson(string $json): Preset
    {
        try {
            $data = json_decode($json, true, 64, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw InvalidPresetException::at('', 'Invalid JSON ('.$e->getMessage().').');
        }

        if (!\is_array($data)) {
            throw InvalidPresetException::at('', 'The preset must be a JSON object.');
        }

        if (Preset::FORMAT !== ($data['format'] ?? null)) {
            throw InvalidPresetException::at('format', \sprintf('Expected "%s".', Preset::FORMAT));
        }

        if (Preset::VERSION !== ($data['version'] ?? null)) {
            throw InvalidPresetException::at('version', \sprintf('Unsupported version, expected %d.', Preset::VERSION));
        }

        $groups = $data['groups'] ?? [];

        if (!\is_array($groups) || !array_is_list($groups)) {
            throw InvalidPresetException::at('groups', 'Expected a list.');
        }

        $result = [];
        $aliases = [];

        foreach ($groups as $i => $group) {
            $group = self::parseGroup($group, \sprintf('groups[%d]', $i));

            if (isset($aliases[$group->alias])) {
                throw InvalidPresetException::at(\sprintf('groups[%d].alias', $i), \sprintf('Duplicate alias "%s".', $group->alias));
            }

            $aliases[$group->alias] = true;
            $result[] = $group;
        }

        return new Preset($result);
    }

    public static function toJson(Preset $preset): string
    {
        $groups = [];

        foreach ($preset->groups as $group) {
            $data = [
                'alias' => $group->alias,
                'title' => $group->title,
                'multiple' => $group->multiple,
                'targets' => (object) $group->targets,
                'classes' => [],
            ];

            if (!$group->published) {
                $data['published'] = false;
            }

            foreach ($group->classes as $class) {
                $classData = ['class' => $class->class, 'label' => $class->label];

                if (!$class->published) {
                    $classData['published'] = false;
                }

                $data['classes'][] = $classData;
            }

            $groups[] = $data;
        }

        $json = json_encode(
            ['format' => Preset::FORMAT, 'version' => Preset::VERSION, 'groups' => $groups],
            \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR,
        );

        return $json."\n";
    }

    private static function parseGroup(mixed $data, string $path): PresetGroup
    {
        if (!\is_array($data) || array_is_list($data)) {
            throw InvalidPresetException::at($path, 'Expected an object.');
        }

        $alias = self::requireString($data, 'alias', $path);

        if (!ClassGroupListener::isValidAlias($alias) || \strlen($alias) > 64) {
            throw InvalidPresetException::at($path.'.alias', 'Only a-z, 0-9, "-" and "_" are allowed (max. 64 characters).');
        }

        $targets = $data['targets'] ?? [];

        if (!\is_array($targets) || (array_is_list($targets) && [] !== $targets)) {
            throw InvalidPresetException::at($path.'.targets', 'Expected an object of target => list of types.');
        }

        $parsedTargets = [];

        foreach ($targets as $key => $types) {
            if (!\is_array($types) || !array_is_list($types) || array_filter($types, static fn ($type): bool => !\is_string($type) || '' === $type)) {
                throw InvalidPresetException::at(\sprintf('%s.targets.%s', $path, $key), 'Expected a list of type names.');
            }

            $parsedTargets[(string) $key] = array_values(array_unique($types));
        }

        $classes = $data['classes'] ?? [];

        if (!\is_array($classes) || !array_is_list($classes)) {
            throw InvalidPresetException::at($path.'.classes', 'Expected a list.');
        }

        $parsedClasses = [];
        $seen = [];

        foreach ($classes as $i => $class) {
            $classPath = \sprintf('%s.classes[%d]', $path, $i);
            $class = self::parseClass($class, $classPath);

            if (isset($seen[$class->class])) {
                throw InvalidPresetException::at($classPath.'.class', \sprintf('Duplicate class "%s" in this group.', $class->class));
            }

            $seen[$class->class] = true;
            $parsedClasses[] = $class;
        }

        return new PresetGroup(
            $alias,
            self::requireString($data, 'title', $path),
            self::optionalBool($data, 'multiple', $path, false),
            $parsedTargets,
            $parsedClasses,
            self::optionalBool($data, 'published', $path, true),
        );
    }

    private static function parseClass(mixed $data, string $path): PresetClass
    {
        if (!\is_array($data) || array_is_list($data)) {
            throw InvalidPresetException::at($path, 'Expected an object.');
        }

        $tokens = ClassAttributeMerger::tokenize(self::requireString($data, 'class', $path));

        foreach ($tokens as $token) {
            if (!ClassListener::isValidClass($token)) {
                throw InvalidPresetException::at($path.'.class', \sprintf('"%s" is not a valid CSS class name.', $token));
            }
        }

        return new PresetClass(
            implode(' ', array_unique($tokens)),
            self::requireString($data, 'label', $path),
            self::optionalBool($data, 'published', $path, true),
        );
    }

    /**
     * @param array<mixed> $data
     */
    private static function requireString(array $data, string $key, string $path): string
    {
        $value = $data[$key] ?? null;

        if (!\is_string($value) || '' === trim($value)) {
            throw InvalidPresetException::at($path.'.'.$key, 'Required, expected a non-empty string.');
        }

        return trim($value);
    }

    /**
     * @param array<mixed> $data
     */
    private static function optionalBool(array $data, string $key, string $path, bool $default): bool
    {
        $value = $data[$key] ?? $default;

        if (!\is_bool($value)) {
            throw InvalidPresetException::at($path.'.'.$key, 'Expected true or false.');
        }

        return $value;
    }
}
