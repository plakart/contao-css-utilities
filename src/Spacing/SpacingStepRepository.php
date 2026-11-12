<?php

declare(strict_types=1);

namespace Plakart\CssUtilitiesBundle\Spacing;

use Doctrine\DBAL\Connection;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Additional spacing steps (13+) defined in the back end. Their CSS comes from the theme.
 */
class SpacingStepRepository implements ResetInterface
{
    /**
     * @var array<int, string>|null
     */
    private ?array $steps = null;

    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @return array<int, string> step => label, ordered by step
     */
    public function findAll(): array
    {
        if (null === $this->steps) {
            $this->steps = [];

            if ($this->connection->createSchemaManager()->tablesExist(['tl_pcu_spacing'])) {
                foreach ($this->connection->fetchAllAssociative('SELECT step, label FROM tl_pcu_spacing ORDER BY step') as $row) {
                    $this->steps[(int) $row['step']] = (string) $row['label'];
                }
            }
        }

        return $this->steps;
    }

    public function reset(): void
    {
        $this->steps = null;
    }
}
