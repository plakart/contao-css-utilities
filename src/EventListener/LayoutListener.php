<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\EventListener;

use Contao\CoreBundle\Event\LayoutEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Loads utilities.css for pages rendered with a modern (Twig) page layout.
 *
 * Modern layouts do not run the generatePage hook, so the GeneratePageListener
 * never fires for them. The layout template reads $GLOBALS['TL_CSS'] lazily
 * when rendering the head, so adding the stylesheet here is early enough.
 */
#[AsEventListener]
class LayoutListener
{
    public function __invoke(LayoutEvent $event): void
    {
        if ($event->getLayout()?->disableUtilityCss) {
            return;
        }

        $GLOBALS['TL_CSS'][] = GeneratePageListener::STYLESHEET;
    }
}
