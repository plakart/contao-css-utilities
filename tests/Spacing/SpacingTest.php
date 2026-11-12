<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\Spacing;

use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\EventListener\DataContainer\SpacingListener;
use Plakart\CssUtilitiesBundle\EventListener\DataContainer\SpacingOptionsListener;
use Plakart\CssUtilitiesBundle\Spacing\SpacingStepRepository;
use Plakart\CssUtilitiesBundle\Tests\DatabaseTestTrait;
use Plakart\CssUtilitiesBundle\Util\UtilityClasses;

final class SpacingTest extends TestCase
{
    use DatabaseTestTrait;

    public function testOptionsContainFixedAndAdditionalSteps(): void
    {
        $connection = self::createConnection();
        $connection->insert('tl_pcu_spacing', ['step' => 20, 'label' => '']);
        $connection->insert('tl_pcu_spacing', ['step' => 13, 'label' => 'Hero']);

        $options = (new SpacingOptionsListener(new SpacingStepRepository($connection)))();

        self::assertSame([...range(0, 12), 13, 20], array_keys($options));
        self::assertSame('12', $options[12]);
        self::assertSame('13 – Hero', $options[13]);
        self::assertSame('20', $options[20]);
    }

    public function testRepositoryWithoutTable(): void
    {
        $connection = self::createConnection();
        $connection->executeStatement('DROP TABLE tl_pcu_spacing');

        self::assertSame([], new SpacingStepRepository($connection)->findAll());
    }

    public function testStepValidation(): void
    {
        $listener = new SpacingListener();

        self::assertSame(13, $listener->validateStep('13'));
        self::assertSame(250, $listener->validateStep(250));

        foreach (['12', '0', '-13', '13.5', 'abc', ''] as $invalid) {
            try {
                $listener->validateStep($invalid);
                self::fail(\sprintf('"%s" must be rejected.', $invalid));
            } catch (\RuntimeException) {
            }
        }
    }

    public function testSnippet(): void
    {
        self::assertSame(
            "html:root {\n  --pcu-space-13: 20rem; /* adjust */\n}\n\n"
            .".mt-13 { margin-top: var(--pcu-space-13) !important; }\n"
            .".mb-13 { margin-bottom: var(--pcu-space-13) !important; }\n"
            .".pt-13 { padding-top: var(--pcu-space-13) !important; }\n"
            .'.pb-13 { padding-bottom: var(--pcu-space-13) !important; }',
            SpacingListener::snippet('13'),
        );
    }

    public function testUtilityClassPattern(): void
    {
        foreach (['mt-0', 'mb-12', 'pt-13', 'pb-250'] as $class) {
            self::assertTrue(UtilityClasses::isUtilityClass($class), $class);
        }

        foreach (['mt-auto', 'mx-4', 'mt-', 'mt-1a', 'xmt-1', 'mt--1'] as $class) {
            self::assertFalse(UtilityClasses::isUtilityClass($class), $class);
        }
    }
}
