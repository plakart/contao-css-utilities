<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Tests\ClassSelection;

use PHPUnit\Framework\TestCase;
use Plakart\CssUtilitiesBundle\ClassSelection\ClassGroup;
use Plakart\CssUtilitiesBundle\ClassSelection\ClassGroupRepository;
use Plakart\CssUtilitiesBundle\Tests\DatabaseTestTrait;

final class ClassGroupRepositoryTest extends TestCase
{
    use DatabaseTestTrait;

    public function testFiltersByTargetAndType(): void
    {
        $connection = self::createConnection();

        self::insertGroup($connection, ['title' => 'Buttons', 'alias' => 'buttons', 'targets' => ['content'], 'contentTypes' => ['hyperlink']], ['btn-a' => 'A']);
        self::insertGroup($connection, ['title' => 'Width', 'alias' => 'width', 'targets' => ['article', 'content']], ['full--width' => 'Full']);

        $repository = new ClassGroupRepository($connection);

        self::assertSame(['buttons', 'width'], self::aliases($repository->findPublishedFor('content', 'hyperlink')));
        self::assertSame(['width'], self::aliases($repository->findPublishedFor('content', 'text')));
        self::assertSame(['width'], self::aliases($repository->findPublishedFor('article', null)));
        self::assertSame([], $repository->findPublishedFor('module', 'navigation'));
    }

    public function testIgnoresTypeRestrictionsOfUncheckedTargets(): void
    {
        $connection = self::createConnection();

        self::insertGroup($connection, ['alias' => 'a', 'targets' => ['module'], 'contentTypes' => ['text']], ['a' => 'A']);

        $group = new ClassGroupRepository($connection)->findPublishedFor('module', 'navigation')[0];

        self::assertSame(['module' => []], $group->targets);
    }

    public function testSkipsUnpublishedAndEmptyGroups(): void
    {
        $connection = self::createConnection();

        self::insertGroup($connection, ['title' => 'A', 'alias' => 'unpublished', 'targets' => ['page'], 'published' => 0], ['a' => 'A']);
        self::insertGroup($connection, ['title' => 'B', 'alias' => 'empty', 'targets' => ['page']]);
        self::insertGroup($connection, ['title' => 'C', 'alias' => 'only-unpublished', 'targets' => ['page']], ['c' => ['label' => 'C', 'published' => 0]]);
        self::insertGroup($connection, ['title' => 'D', 'alias' => 'mixed', 'targets' => ['page']], ['d1' => 'D1', 'd2' => ['label' => 'D2', 'published' => 0]]);

        $groups = new ClassGroupRepository($connection)->findPublishedFor('page', null);

        self::assertSame(['mixed'], self::aliases($groups));
        self::assertSame(['d1' => 'D1'], $groups[0]->classes);
    }

    public function testOrdersGroupsByTitleAndClassesBySorting(): void
    {
        $connection = self::createConnection();

        self::insertGroup($connection, ['title' => 'Zeta', 'alias' => 'zeta', 'targets' => ['page'], 'multiple' => 1], [
            'second' => ['label' => 'Second', 'sorting' => 256],
            'first' => ['label' => 'First', 'sorting' => 128],
        ]);
        self::insertGroup($connection, ['title' => 'Alpha', 'alias' => 'alpha', 'targets' => ['page']], ['x' => 'X']);

        $groups = new ClassGroupRepository($connection)->findPublishedFor('page', null);

        self::assertSame(['alpha', 'zeta'], self::aliases($groups));
        self::assertSame(['first' => 'First', 'second' => 'Second'], $groups[1]->classes);
        self::assertTrue($groups[1]->multiple);
        self::assertSame('Zeta', $groups[1]->title);
    }

    public function testAllClassTokensIncludeUnpublishedAndSplitMultiTokenClasses(): void
    {
        $connection = self::createConnection();

        self::insertGroup($connection, ['alias' => 'a', 'targets' => ['page'], 'published' => 0], [
            'btn btn-primary' => 'Primary',
            'btn btn-secondary' => ['label' => 'Secondary', 'published' => 0],
        ]);

        self::assertSame(
            ['btn', 'btn-primary', 'btn-secondary'],
            new ClassGroupRepository($connection)->findAllClassTokens(),
        );
    }

    public function testMemoizesUntilReset(): void
    {
        $connection = self::createConnection();
        $repository = new ClassGroupRepository($connection);

        self::assertSame([], $repository->findPublishedFor('page', null));

        self::insertGroup($connection, ['alias' => 'a', 'targets' => ['page']], ['a' => 'A']);
        self::assertSame([], $repository->findPublishedFor('page', null));

        $repository->reset();
        self::assertCount(1, $repository->findPublishedFor('page', null));
    }

    /**
     * @param list<ClassGroup> $groups
     *
     * @return list<string>
     */
    private static function aliases(array $groups): array
    {
        return array_map(static fn (ClassGroup $group): string => $group->alias, $groups);
    }
}
