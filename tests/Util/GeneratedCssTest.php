<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\Util;

use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\Util\UtilityClasses;

final class GeneratedCssTest extends TestCase
{
    public function testCommittedCssMatchesGeneratorOutput(): void
    {
        $file = \dirname(__DIR__, 2).'/public/css/utilities.css';

        self::assertFileExists($file, 'Run "composer generate-css" and commit the result.');
        self::assertStringEqualsFile($file, UtilityClasses::generateCss());
    }
}
