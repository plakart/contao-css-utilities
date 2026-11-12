<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\EventListener\DataContainer;

use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\EventListener\DataContainer\ClassListener;

final class ClassListenerTest extends TestCase
{
    public function testClassValidation(): void
    {
        foreach (['btn', 'btn-primary', 'full--width', 'btn_hyperlink_primary', '-foo', '_bar'] as $class) {
            self::assertTrue(ClassListener::isValidClass($class), $class);
        }

        foreach (['1col', '--foo', 'foo.bar', 'foo:bar', 'ä', ''] as $class) {
            self::assertFalse(ClassListener::isValidClass($class), $class);
        }
    }
}
