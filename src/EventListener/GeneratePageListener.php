<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\PageRegular;

#[AsHook('generatePage')]
class GeneratePageListener
{
    public function __invoke(PageModel $pageModel, LayoutModel $layout, PageRegular $pageRegular): void
    {
        if ($layout->disableUtilityCss) {
            return;
        }

        $GLOBALS['TL_CSS'][] = 'bundles/plakartcssutilities/css/utilities.css|static';
    }
}
