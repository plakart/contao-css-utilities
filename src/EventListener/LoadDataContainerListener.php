<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Plakart\CssUtilitiesBundle\EventListener\DataContainer\ClassAttributeListener;
use Plakart\CssUtilitiesBundle\EventListener\DataContainer\ClassSelectionPaletteListener;
use Plakart\CssUtilitiesBundle\Target\TargetRegistry;

/**
 * Adds the class selection field and its callbacks to every active target table.
 *
 * Done in a hook instead of contao/dca/*.php so that tables of optional bundles
 * (tl_news, tl_calendar_events) are only extended when the bundle is installed.
 * Must not query the database: the hook also runs while building the schema.
 */
#[AsHook('loadDataContainer')]
class LoadDataContainerListener
{
    public const FIELD = 'pcuClasses';

    public function __construct(private readonly TargetRegistry $targets)
    {
    }

    public function __invoke(string $table): void
    {
        if (null === $this->targets->forTable($table) || !isset($GLOBALS['TL_DCA'][$table])) {
            return;
        }

        $GLOBALS['TL_DCA'][$table]['fields'][self::FIELD] = [
            'label' => &$GLOBALS['TL_LANG']['MSC']['pcuClasses'],
            'exclude' => true,
            'inputType' => 'pcuClassSelect',
            'eval' => ['tl_class' => 'clr'],
            'sql' => ['type' => 'blob', 'length' => 65535, 'notnull' => false],
        ];

        $GLOBALS['TL_DCA'][$table]['config']['onload_callback'][] = [ClassSelectionPaletteListener::class, '__invoke'];
        $GLOBALS['TL_DCA'][$table]['config']['onsubmit_callback'][] = [ClassAttributeListener::class, '__invoke'];
    }
}
