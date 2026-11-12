<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Plakart\CssUtilitiesBundle\Util\ClassAttributeMerger;

/**
 * DCA callbacks of tl_pcu_class.
 */
class ClassListener
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * Normalizes and validates the CSS class and keeps it unique within its group.
     */
    #[AsCallback('tl_pcu_class', 'fields.cssClass.save')]
    public function saveCssClass(mixed $value, DataContainer $dc): string
    {
        $tokens = ClassAttributeMerger::tokenize(ltrim(trim((string) $value), '.'));

        foreach ($tokens as $token) {
            if (!self::isValidClass($token)) {
                throw new \RuntimeException(\sprintf($GLOBALS['TL_LANG']['tl_pcu_class']['invalidClass'] ?? '"%s" is not a valid CSS class name.', $token));
            }
        }

        $value = implode(' ', array_unique($tokens));
        $pid = (int) ($dc->getCurrentRecord()['pid'] ?? 0);

        $duplicate = $this->connection->fetchOne(
            'SELECT id FROM tl_pcu_class WHERE pid = ? AND cssClass = ? AND id != ?',
            [$pid, $value, (int) $dc->id],
        );

        if (false !== $duplicate) {
            throw new \RuntimeException(\sprintf($GLOBALS['TL_LANG']['tl_pcu_class']['duplicateClass'] ?? 'The class "%s" already exists in this group.', $value));
        }

        return $value;
    }

    public static function isValidClass(string $class): bool
    {
        return 1 === preg_match('/^-?[_a-zA-Z][_a-zA-Z0-9-]*$/', $class);
    }
}
