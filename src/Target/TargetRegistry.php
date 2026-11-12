<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Target;

/**
 * Knows all tables that can receive CSS classes and filters them by the installed bundles.
 */
final class TargetRegistry
{
    /**
     * @var array<string, Target>
     */
    private readonly array $targets;

    /**
     * @param array<string, string> $bundles the kernel.bundles parameter
     */
    public function __construct(array $bundles)
    {
        $targets = [];

        foreach (self::definitions() as $target) {
            if (null !== $target->requiredBundle && !isset($bundles[$target->requiredBundle])) {
                continue;
            }

            $targets[$target->key] = $target;
        }

        $this->targets = $targets;
    }

    /**
     * @return array<string, Target>
     */
    public function all(): array
    {
        return $this->targets;
    }

    public function has(string $key): bool
    {
        return isset($this->targets[$key]);
    }

    public function get(string $key): Target
    {
        return $this->targets[$key] ?? throw new \InvalidArgumentException(\sprintf('Unknown or inactive target "%s".', $key));
    }

    public function forTable(string $table): ?Target
    {
        foreach ($this->targets as $target) {
            if ($target->table === $table) {
                return $target;
            }
        }

        return null;
    }

    /**
     * @return list<Target>
     */
    private static function definitions(): array
    {
        return [
            new Target('content', 'tl_content', 'cssID', 'type'),
            new Target('article', 'tl_article', 'cssID'),
            new Target('form_field', 'tl_form_field', 'class', 'type'),
            new Target('module', 'tl_module', 'cssID', 'type'),
            new Target('page', 'tl_page', 'cssClass'),
            new Target('news', 'tl_news', 'cssClass', null, 'ContaoNewsBundle'),
            new Target('event', 'tl_calendar_events', 'cssClass', null, 'ContaoCalendarBundle'),
        ];
    }
}
