<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\EventListener\DataContainer;

use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\EventListener\DataContainer\UtilityClassLabelListener;

final class UtilityClassLabelListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testAppendsClassesToCardHeaderLabelArray(): void
    {
        $label = UtilityClassLabelListener::appendClasses(
            ['Text', '<p>Preview</p>', 'published'],
            ['utilityMt' => '4', 'utilityMb' => '', 'utilityPt' => '2', 'utilityPb' => '12'],
        );

        self::assertSame(
            ['Text <span class="tl_gray pcu-utilities">mt-4 pt-2 pb-12</span>', '<p>Preview</p>', 'published'],
            $label,
        );
    }

    public function testAppendsClassesToStringLabel(): void
    {
        $label = UtilityClassLabelListener::appendClasses('Headline', ['utilityMb' => '3']);

        self::assertSame('Headline <span class="tl_gray pcu-utilities">mb-3</span>', $label);
    }

    public function testLeavesLabelUntouchedWithoutSelection(): void
    {
        $row = ['utilityMt' => '', 'utilityMb' => '', 'utilityPt' => '', 'utilityPb' => ''];

        self::assertSame('Text', UtilityClassLabelListener::appendClasses('Text', $row));
        self::assertSame(['Text', '', ''], UtilityClassLabelListener::appendClasses(['Text', '', ''], $row));
    }

    public function testStepZeroIsShown(): void
    {
        $label = UtilityClassLabelListener::appendClasses('Text', ['utilityMt' => '0']);

        self::assertSame('Text <span class="tl_gray pcu-utilities">mt-0</span>', $label);
    }

    public function testWrapsExistingLabelCallback(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['list']['label']['label_callback'] =
            static fn (array $row, string $label): array => ['Original: '.$row['type'], '<p>Preview</p>', 'published'];

        $listener = new UtilityClassLabelListener();
        $listener();

        $callback = $GLOBALS['TL_DCA']['tl_content']['list']['label']['label_callback'];

        self::assertInstanceOf(\Closure::class, $callback);

        $result = $callback(['type' => 'text', 'utilityMt' => '4'], 'unused');

        self::assertSame(
            ['Original: text <span class="tl_gray pcu-utilities">mt-4</span>', '<p>Preview</p>', 'published'],
            $result,
        );
    }

    public function testFallsBackToPassedLabelWithoutExistingCallback(): void
    {
        $GLOBALS['TL_DCA']['tl_content'] = ['list' => ['label' => []]];

        $listener = new UtilityClassLabelListener();
        $listener();

        $callback = $GLOBALS['TL_DCA']['tl_content']['list']['label']['label_callback'];

        self::assertSame(
            'Fallback <span class="tl_gray pcu-utilities">pb-9</span>',
            $callback(['utilityPb' => '9'], 'Fallback'),
        );
    }

    public function testDoesNotWrapItselfTwice(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['list']['label']['label_callback'] =
            static fn (array $row, string $label): string => 'Original';

        $listener = new UtilityClassLabelListener();
        $listener();
        $listener();

        $callback = $GLOBALS['TL_DCA']['tl_content']['list']['label']['label_callback'];

        self::assertSame(
            'Original <span class="tl_gray pcu-utilities">mt-1</span>',
            $callback(['utilityMt' => '1'], 'unused'),
        );
    }
}
